<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { BackspaceIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    players: {
        type: Array,
        default: () => [],
    },
});

const selected = ref(null);
const shake = ref(false);

const form = useForm({
    user_id: null,
    pin: '',
});

const dots = computed(() => Array.from({ length: 4 }, (_, i) => i < form.pin.length));

function selectPlayer(player) {
    selected.value = player;
    form.reset();
    form.clearErrors();
    form.user_id = player.id;
}

function back() {
    selected.value = null;
    form.reset();
    form.clearErrors();
}

function press(digit) {
    if (form.pin.length >= 4 || form.processing) return;
    form.pin += String(digit);
}

function backspace() {
    form.pin = form.pin.slice(0, -1);
}

watch(() => form.pin, (val) => {
    if (val.length === 4) {
        form.post('/play/login', {
            preserveScroll: true,
            onError: () => {
                shake.value = true;
                form.pin = '';
                setTimeout(() => (shake.value = false), 500);
            },
        });
    }
});
</script>

<template>
    <Head title="Intră în joc" />

    <div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-pitch-950 px-4 py-10 text-slate-100">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 h-[32rem] w-[32rem] rounded-full bg-emerald-500/20 blur-[120px]"></div>
            <div class="absolute top-1/3 -right-40 h-[28rem] w-[28rem] rounded-full bg-blue-600/20 blur-[120px]"></div>
            <div class="absolute bottom-0 left-1/4 h-[24rem] w-[24rem] rounded-full bg-violet-600/10 blur-[120px]"></div>
        </div>

        <div class="relative w-full max-w-xl">
            <div class="mb-10 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400 to-blue-500 text-3xl shadow-xl shadow-emerald-500/30">⚽</div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">FIFA League</h1>
                <p class="mt-1 text-sm text-slate-400">Alege-ți jucătorul și intră în joc</p>
            </div>

            <transition mode="out-in" enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="!selected" key="grid" class="grid grid-cols-2 gap-4 sm:grid-cols-2">
                    <button
                        v-for="player in players"
                        :key="player.id"
                        @click="selectPlayer(player)"
                        class="group flex flex-col items-center gap-3 rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl transition hover:-translate-y-1 hover:border-white/20 hover:bg-white/10 hover:shadow-2xl"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl text-3xl shadow-inner transition group-hover:scale-105"
                            :style="{ backgroundColor: (player.avatar_color || '#334155') + '33', border: `1px solid ${player.avatar_color || '#334155'}` }"
                        >
                            {{ player.avatar_emoji || '🎮' }}
                        </div>
                        <span class="font-display font-semibold text-white">{{ player.name }}</span>
                    </button>

                    <p v-if="players.length === 0" class="col-span-2 text-center text-sm text-slate-500">
                        Niciun jucător configurat încă.
                    </p>
                </div>

                <div v-else key="pin" class="mx-auto max-w-xs rounded-3xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-xl" :class="{ 'animate-[shake_0.4s]': shake }">
                    <button @click="back" class="mb-4 inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white">
                        <ArrowLeftIcon class="h-4 w-4" /> Înapoi
                    </button>

                    <div
                        class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl text-3xl"
                        :style="{ backgroundColor: (selected.avatar_color || '#334155') + '33', border: `1px solid ${selected.avatar_color || '#334155'}` }"
                    >
                        {{ selected.avatar_emoji || '🎮' }}
                    </div>
                    <p class="mb-4 font-display font-semibold text-white">{{ selected.name }}</p>

                    <div class="mb-6 flex justify-center gap-3">
                        <span
                            v-for="(filled, i) in dots"
                            :key="i"
                            class="h-3.5 w-3.5 rounded-full border transition"
                            :class="filled ? 'border-emerald-400 bg-emerald-400' : 'border-slate-600 bg-transparent'"
                        />
                    </div>

                    <p v-if="form.errors.pin" class="mb-3 text-sm font-medium text-rose-400">{{ form.errors.pin }}</p>

                    <div class="grid grid-cols-3 gap-3">
                        <button
                            v-for="digit in [1,2,3,4,5,6,7,8,9]"
                            :key="digit"
                            @click="press(digit)"
                            class="rounded-2xl bg-white/5 py-4 font-display text-xl font-semibold text-white transition hover:bg-white/15 active:scale-95"
                        >
                            {{ digit }}
                        </button>
                        <div></div>
                        <button @click="press(0)" class="rounded-2xl bg-white/5 py-4 font-display text-xl font-semibold text-white transition hover:bg-white/15 active:scale-95">0</button>
                        <button @click="backspace" class="flex items-center justify-center rounded-2xl bg-white/5 py-4 text-white transition hover:bg-white/15 active:scale-95">
                            <BackspaceIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<style>
@keyframes shake {
    10%, 90% { transform: translateX(-2px); }
    20%, 80% { transform: translateX(4px); }
    30%, 50%, 70% { transform: translateX(-8px); }
    40%, 60% { transform: translateX(8px); }
}
</style>
