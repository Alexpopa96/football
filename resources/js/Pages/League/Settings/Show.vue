<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { KeyIcon, TrophyIcon, ArrowTrendingUpIcon, ShieldCheckIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    player: Object,
    stats: Object,
    trophies: Object,
    ratingHistory: Array,
    teamStats: Object,
    matches: Array,
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

const statTiles = computed(() => props.stats ? [
    { label: 'Meciuri', value: props.stats.played },
    { label: 'Victorii', value: props.stats.won },
    { label: 'Egaluri', value: props.stats.drawn },
    { label: 'Înfrângeri', value: props.stats.lost },
    { label: 'Win rate', value: `${props.stats.win_rate}%` },
    { label: 'Gol diferență', value: props.stats.goal_difference },
    { label: 'Hattrick-uri', value: props.stats.hattricks },
    { label: 'Victorie mare', value: props.stats.best_win_margin },
    { label: 'Serie victorii', value: props.stats.longest_win_streak },
] : []);

const trophyList = computed(() => [
    ...(props.trophies?.championships ?? []).map((t) => ({ ...t, kind: 'Campionat' })),
    ...(props.trophies?.cups ?? []).map((t) => ({ ...t, kind: 'Cupă' })),
].sort((a, b) => new Date(b.completed_at) - new Date(a.completed_at)));

const sparkline = computed(() => {
    const history = props.ratingHistory ?? [];
    if (history.length < 2) return null;

    const width = 300;
    const height = 64;
    const ratings = history.map((h) => h.rating);
    const min = Math.min(...ratings);
    const max = Math.max(...ratings);
    const range = max - min || 1;
    const step = width / (history.length - 1);

    const points = history
        .map((h, i) => `${(i * step).toFixed(1)},${(height - ((h.rating - min) / range) * height).toFixed(1)}`)
        .join(' ');

    return { points, width, height, min, max };
});

const resultClass = (result) =>
    ({
        W: 'bg-emerald-500/15 text-emerald-300',
        D: 'bg-slate-500/15 text-slate-300',
        L: 'bg-rose-500/15 text-rose-300',
    })[result];

const resultLabel = (result) => ({ W: 'V', D: 'E', L: 'Î' })[result];

const formatDate = (value) =>
    value
        ? new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' })
        : '';
</script>

<template>
    <LeagueLayout title="Setări">
        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Setări</h1>
            <p class="mt-1 text-slate-400">Statisticile și contul tău. Doar tu vezi această pagină.</p>
        </div>

        <!-- Header -->
        <div class="mb-6 flex items-center gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
            <div
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-3xl"
                :style="{ backgroundColor: (player.avatar_color || '#334155') + '33', border: `1px solid ${player.avatar_color || '#334155'}` }"
            >
                {{ player.avatar_emoji || '🎮' }}
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="font-display truncate text-xl font-extrabold tracking-tight text-white">{{ player.name }}</h2>
                <p v-if="stats" class="mt-1 text-sm text-slate-400">{{ stats.championships_played }} campionate jucate</p>
            </div>
            <div v-if="stats" class="shrink-0 text-right">
                <p class="font-display text-3xl font-extrabold text-white">{{ stats.rating }}</p>
                <p class="text-[11px] text-slate-500">rating</p>
            </div>
        </div>

        <template v-if="stats">
            <!-- Career stats -->
            <div class="mb-6 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                    <div v-for="tile in statTiles" :key="tile.label" class="rounded-2xl bg-white/[0.03] px-3 py-3 text-center">
                        <p class="font-display text-xl font-extrabold text-white">{{ tile.value }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ tile.label }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid gap-6 lg:grid-cols-2">
                <!-- Trophy cabinet -->
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                    <div class="mb-4 flex items-center gap-2">
                        <TrophyIcon class="h-5 w-5 text-amber-400" />
                        <h2 class="font-display text-lg font-bold text-white">Vitrină trofee</h2>
                    </div>

                    <div class="space-y-2">
                        <div v-for="trophy in trophyList" :key="`${trophy.kind}-${trophy.id}`" class="flex items-center gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5">
                            <TrophyIcon class="h-5 w-5 shrink-0 text-amber-400" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ trophy.name }}</p>
                                <p class="text-[11px] text-slate-500">{{ trophy.kind }} &middot; {{ formatDate(trophy.completed_at) }}</p>
                            </div>
                        </div>

                        <p v-if="trophyList.length === 0" class="py-6 text-center text-sm text-slate-400">Nicio cupă câștigată încă.</p>
                    </div>
                </div>

                <!-- Rating evolution -->
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                    <div class="mb-4 flex items-center gap-2">
                        <ArrowTrendingUpIcon class="h-5 w-5 text-blue-400" />
                        <h2 class="font-display text-lg font-bold text-white">Evoluție rating</h2>
                    </div>

                    <div v-if="sparkline" class="rounded-2xl bg-white/[0.03] p-4">
                        <svg :viewBox="`0 0 ${sparkline.width} ${sparkline.height}`" class="h-16 w-full">
                            <polyline :points="sparkline.points" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                        </svg>
                        <div class="mt-2 flex justify-between text-[11px] text-slate-500">
                            <span>{{ sparkline.min }}</span>
                            <span>{{ sparkline.max }}</span>
                        </div>
                    </div>
                    <p v-else class="py-6 text-center text-sm text-slate-400">Nu sunt destule meciuri pentru un grafic încă.</p>

                    <div v-if="teamStats?.best_team" class="mt-4 flex items-center gap-2 rounded-2xl bg-white/[0.03] px-3 py-2.5">
                        <ShieldCheckIcon class="h-5 w-5 shrink-0 text-emerald-400" />
                        <TeamCrest :team="teamStats.best_team" size="h-6 w-6" />
                        <span class="truncate text-xs font-medium text-emerald-300">{{ teamStats.best_team.short_name }}</span>
                        <span class="ml-auto shrink-0 text-[11px] text-slate-500">{{ teamStats.best_team.win_rate }}% &middot; echipa preferată</span>
                    </div>
                </div>
            </div>

            <!-- Match history -->
            <div class="mb-6 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <div class="mb-4 flex items-center gap-2">
                    <ClockIcon class="h-5 w-5 text-slate-400" />
                    <h2 class="font-display text-lg font-bold text-white">Istoric meciuri</h2>
                </div>

                <div class="space-y-2">
                    <div v-for="m in matches" :key="m.id" class="flex items-center gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5">
                        <span class="w-6 shrink-0 rounded-lg py-1 text-center text-xs font-bold" :class="resultClass(m.result)">{{ resultLabel(m.result) }}</span>
                        <TeamCrest :team="m.my_team" size="h-7 w-7" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">vs {{ m.opponent_name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ m.competition_name }} &middot; {{ formatDate(m.played_at) }}</p>
                        </div>
                        <TeamCrest :team="m.opponent_team" size="h-7 w-7" />
                        <p class="w-12 shrink-0 text-right font-display text-base font-bold text-white">{{ m.my_score }}-{{ m.opponent_score }}</p>
                    </div>

                    <p v-if="matches.length === 0" class="py-6 text-center text-sm text-slate-400">Niciun meci jucat încă.</p>
                </div>
            </div>
        </template>

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
