<script setup>
import { useForm } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import { KeyIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    player: Object,
});

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: '',
});

function submit() {
    form.put('/league/settings/pin', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <LeagueLayout title="Setări">
        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Setări</h1>
            <p class="mt-1 text-slate-400">Doar tu poți vedea și schimba PIN-ul tău.</p>
        </div>

        <div class="mb-6 flex flex-col items-center gap-3 rounded-3xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-xl">
            <div
                class="flex h-16 w-16 items-center justify-center rounded-2xl text-3xl"
                :style="{ backgroundColor: (player.avatar_color || '#334155') + '33', border: `1px solid ${player.avatar_color || '#334155'}` }"
            >
                {{ player.avatar_emoji || '🎮' }}
            </div>
            <p class="font-display font-semibold text-white">{{ player.name }}</p>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-4 flex items-center gap-2">
                <KeyIcon class="h-5 w-5 text-emerald-400" />
                <h2 class="font-display text-lg font-bold text-white">Schimbă PIN-ul</h2>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-xs font-medium text-slate-300">PIN actual</label>
                    <input
                        v-model="form.current_pin"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="••••"
                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400"
                    />
                    <p v-if="form.errors.current_pin" class="mt-1 text-xs text-rose-400">{{ form.errors.current_pin }}</p>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-300">PIN nou (4 cifre)</label>
                    <input
                        v-model="form.pin"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="••••"
                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400"
                    />
                    <p v-if="form.errors.pin" class="mt-1 text-xs text-rose-400">{{ form.errors.pin }}</p>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-300">Confirmă PIN-ul nou</label>
                    <input
                        v-model="form.pin_confirmation"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="••••"
                        class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400"
                    />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2 text-sm font-semibold text-pitch-950 disabled:opacity-50">
                        Salvează PIN-ul
                    </button>
                </div>
            </form>
        </div>
    </LeagueLayout>
</template>
