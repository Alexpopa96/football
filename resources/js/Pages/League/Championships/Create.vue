<script setup>
import { useForm } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';

const props = defineProps({
    players: Array,
});

const form = useForm({
    name: '',
    player_ids: [],
});

function toggle(id) {
    const idx = form.player_ids.indexOf(id);
    if (idx >= 0) {
        form.player_ids.splice(idx, 1);
    } else if (form.player_ids.length < 4) {
        form.player_ids.push(id);
    }
}

function submit() {
    form.post('/league/championships/store');
}
</script>

<template>
    <LeagueLayout title="Campionat nou">
        <div class="mx-auto max-w-2xl">
            <h1 class="mb-1 font-display text-3xl font-extrabold tracking-tight text-white">Campionat nou</h1>
            <p class="mb-8 text-slate-400">Dă-i un nume și alege exact 4 jucători.</p>

            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label class="text-xs font-medium text-slate-300">Numele campionatului</label>
                    <input v-model="form.name" type="text" placeholder="ex: Sezonul 1" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-300">Jucători ({{ form.player_ids.length }}/4)</label>
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <button
                            v-for="player in players"
                            :key="player.id"
                            type="button"
                            @click="toggle(player.id)"
                            class="flex flex-col items-center gap-2 rounded-2xl border p-4 transition"
                            :class="form.player_ids.includes(player.id) ? 'border-emerald-400 bg-emerald-400/10' : 'border-white/10 bg-white/5 hover:bg-white/10'"
                        >
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl text-2xl" :style="{ backgroundColor: (player.avatar_color || '#334155') + '33', border: `1px solid ${player.avatar_color || '#334155'}` }">
                                {{ player.avatar_emoji || '🎮' }}
                            </div>
                            <span class="text-sm font-medium text-white">{{ player.name }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.player_ids" class="mt-1 text-xs text-rose-400">{{ form.errors.player_ids }}</p>
                </div>

                <button type="submit" :disabled="form.processing || form.player_ids.length !== 4 || !form.name" class="w-full rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 py-3 font-display font-semibold text-pitch-950 shadow-lg shadow-emerald-500/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40">
                    Creează campionatul
                </button>
            </form>
        </div>
    </LeagueLayout>
</template>
