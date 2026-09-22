<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { TrophyIcon, FireIcon, ShieldCheckIcon, SparklesIcon, FaceFrownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    players: Array,
    matches: Array,
    teamStats: {
        type: Object,
        default: () => ({ players: [], cursed_teams: [], lucky_teams: [] }),
    },
});

const metrics = [
    { key: 'rating', label: 'Rating' },
    { key: 'points', label: 'Puncte' },
    { key: 'won', label: 'Victorii' },
    { key: 'played', label: 'Meciuri' },
    { key: 'goals_for', label: 'Goluri' },
    { key: 'championships_won', label: 'Campionate' },
    { key: 'cups_won', label: 'Cupe' },
    { key: 'hattricks', label: 'Hattrick-uri' },
    { key: 'best_win_margin', label: 'Victorie mare' },
    { key: 'longest_win_streak', label: 'Serie victorii' },
    { key: 'bet_balance', label: 'Sold pariuri' },
];

const activeMetric = ref('rating');

const rankedPlayers = computed(() => {
    return [...props.players]
        .sort((a, b) => b[activeMetric.value] - a[activeMetric.value] || b.win_rate - a.win_rate)
        .map((player, index) => ({ ...player, position: index + 1 }));
});

const playerA = ref(props.players[0]?.user_id ?? null);
const playerB = ref(props.players[1]?.user_id ?? props.players[0]?.user_id ?? null);

const playerById = (id) => props.players.find((p) => p.user_id === id);

const h2hMatches = computed(() => {
    if (!playerA.value || !playerB.value || playerA.value === playerB.value) return [];

    return props.matches
        .filter(
            (m) =>
                (m.home_user_id === playerA.value && m.away_user_id === playerB.value) ||
                (m.home_user_id === playerB.value && m.away_user_id === playerA.value)
        );
});

const h2hStats = computed(() => {
    const stats = { a: { won: 0, drawn: 0, lost: 0, goals: 0 }, b: { won: 0, drawn: 0, lost: 0, goals: 0 } };

    for (const m of h2hMatches.value) {
        const aScore = m.home_user_id === playerA.value ? m.home_score : m.away_score;
        const bScore = m.home_user_id === playerA.value ? m.away_score : m.home_score;

        stats.a.goals += aScore;
        stats.b.goals += bScore;

        if (aScore > bScore) {
            stats.a.won++;
            stats.b.lost++;
        } else if (aScore < bScore) {
            stats.b.won++;
            stats.a.lost++;
        } else {
            stats.a.drawn++;
            stats.b.drawn++;
        }
    }

    return stats;
});

const formatDate = (value) =>
    value
        ? new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' })
        : '';
</script>

<template>
    <LeagueLayout title="Statistici">
        <div class="mb-6">
            <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Statistici</h1>
            <p class="mt-1 text-slate-400">Cine domină liga, meci de meci.</p>
        </div>

        <!-- Leaderboard -->
        <div class="mb-8 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
            <div class="mb-4 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <TrophyIcon class="h-5 w-5 text-amber-400" />
                    <h2 class="font-display text-lg font-bold text-white">Clasament jucători</h2>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap gap-2">
                <button
                    v-for="metric in metrics"
                    :key="metric.key"
                    @click="activeMetric = metric.key"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold transition"
                    :class="activeMetric === metric.key ? 'bg-gradient-to-r from-emerald-400 to-blue-500 text-pitch-950' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
                >
                    {{ metric.label }}
                </button>
            </div>

            <p v-if="activeMetric === 'rating'" class="mb-4 text-xs text-slate-500">
                Scor Elo: urcă sau coboară după fiecare meci, în funcție de rezultat și de puterea adversarului. Arată cine joacă bine acum, separat de trofeele câștigate.
            </p>

            <div class="space-y-2">
                <Link
                    v-for="row in rankedPlayers"
                    :key="row.user_id"
                    :href="route('league.players.show', row.user_id)"
                    class="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition hover:bg-white/[0.06]"
                    :class="row.position === 1 ? 'bg-gradient-to-r from-amber-400/10 to-transparent ring-1 ring-amber-400/20' : 'bg-white/[0.03]'"
                >
                    <span class="w-4 text-center font-display font-bold" :class="row.position === 1 ? 'text-amber-400' : 'text-slate-400'">{{ row.position }}</span>
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg"
                        :style="{ backgroundColor: (row.avatar_color || '#334155') + '33', border: `1px solid ${row.avatar_color || '#334155'}` }"
                    >
                        {{ row.avatar_emoji || '🎮' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ row.name }}</p>
                        <p class="truncate text-[11px] text-slate-500">
                            {{ row.played }}J &middot; {{ row.won }}V {{ row.drawn }}E {{ row.lost }}Î &middot; {{ row.goals_for }}-{{ row.goals_against }}G
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="row.bet_balance > 0 ? 'bg-violet-500/15 text-violet-300' : 'bg-rose-500/15 text-rose-400'"
                        title="Sold din pariuri"
                    >
                        🎲 {{ row.bet_balance }}p
                    </span>
                    <span
                        v-if="row.bankruptcies_count > 0"
                        class="shrink-0 rounded-full bg-rose-500/15 px-2 py-0.5 text-[11px] font-semibold text-rose-400"
                        :title="`A dat faliment de ${row.bankruptcies_count} ori`"
                    >
                        💀 ×{{ row.bankruptcies_count }}
                    </span>
                    <span class="font-display text-lg font-extrabold text-white">{{ row[activeMetric] }}</span>
                </Link>

                <p v-if="rankedPlayers.length === 0" class="py-6 text-center text-sm text-slate-400">Nicio statistică încă.</p>
            </div>
        </div>

        <!-- Best team per player -->
        <div class="mb-8 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
            <div class="mb-4 flex items-center gap-2">
                <ShieldCheckIcon class="h-5 w-5 text-emerald-400" />
                <h2 class="font-display text-lg font-bold text-white">Echipa preferată a fiecărui jucător</h2>
            </div>
            <p class="mb-4 text-xs text-slate-500">
                Echipa cu cel mai mare procent de victorii pentru fiecare jucător (minim 2 meciuri jucați cu ea).
            </p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="row in teamStats.players"
                    :key="row.user_id"
                    class="flex items-center gap-3 rounded-2xl bg-white/[0.03] px-3 py-3"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg"
                        :style="{ backgroundColor: (row.avatar_color || '#334155') + '33', border: `1px solid ${row.avatar_color || '#334155'}` }"
                    >
                        {{ row.avatar_emoji || '🎮' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ row.name }}</p>
                        <div v-if="row.best_team" class="mt-1 flex items-center gap-2">
                            <TeamCrest :team="row.best_team" size="h-5 w-5" />
                            <span class="truncate text-xs font-medium text-emerald-300">{{ row.best_team.short_name }}</span>
                            <span class="shrink-0 text-[11px] text-slate-500">{{ row.best_team.win_rate }}% &middot; {{ row.best_team.won }}V/{{ row.best_team.played }}J</span>
                        </div>
                        <p v-else class="mt-1 text-[11px] text-slate-500">Nu are încă destule meciuri cu o singură echipă.</p>
                    </div>
                </div>

                <p v-if="teamStats.players.length === 0" class="col-span-2 py-6 text-center text-sm text-slate-400">Nicio statistică încă.</p>
            </div>
        </div>

        <!-- Cursed / lucky teams -->
        <div class="mb-8 grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <div class="mb-4 flex items-center gap-2">
                    <SparklesIcon class="h-5 w-5 text-amber-300" />
                    <h2 class="font-display text-lg font-bold text-white">Echipe norocoase</h2>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="(team, index) in teamStats.lucky_teams"
                        :key="team.team_id"
                        class="flex items-center gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5"
                    >
                        <span class="w-4 text-center font-display text-sm font-bold text-slate-400">{{ index + 1 }}</span>
                        <TeamCrest :team="team" size="h-8 w-8" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ team.name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ team.played }}J &middot; {{ team.won }}V {{ team.drawn }}E {{ team.lost }}Î</p>
                        </div>
                        <span class="font-display text-lg font-extrabold text-emerald-400">{{ team.win_rate }}%</span>
                    </div>

                    <p v-if="teamStats.lucky_teams.length === 0" class="py-6 text-center text-sm text-slate-400">Nu sunt destule date încă.</p>
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <div class="mb-4 flex items-center gap-2">
                    <FaceFrownIcon class="h-5 w-5 text-rose-400" />
                    <h2 class="font-display text-lg font-bold text-white">Echipe blestemate</h2>
                </div>

                <div class="space-y-2">
                    <div
                        v-for="(team, index) in teamStats.cursed_teams"
                        :key="team.team_id"
                        class="flex items-center gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5"
                    >
                        <span class="w-4 text-center font-display text-sm font-bold text-slate-400">{{ index + 1 }}</span>
                        <TeamCrest :team="team" size="h-8 w-8" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ team.name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ team.played }}J &middot; {{ team.won }}V {{ team.drawn }}E {{ team.lost }}Î</p>
                        </div>
                        <span class="font-display text-lg font-extrabold text-rose-400">{{ team.win_rate }}%</span>
                    </div>

                    <p v-if="teamStats.cursed_teams.length === 0" class="py-6 text-center text-sm text-slate-400">Nu sunt destule date încă.</p>
                </div>
            </div>
        </div>

        <!-- Head to head -->
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
            <div class="mb-4 flex items-center gap-2">
                <FireIcon class="h-5 w-5 text-rose-400" />
                <h2 class="font-display text-lg font-bold text-white">Meciuri directe</h2>
            </div>

            <div class="mb-5 grid grid-cols-2 gap-3">
                <select v-model="playerA" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:ring-emerald-400">
                    <option v-for="p in players" :key="p.user_id" :value="p.user_id">{{ p.name }}</option>
                </select>
                <select v-model="playerB" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:ring-emerald-400">
                    <option v-for="p in players" :key="p.user_id" :value="p.user_id">{{ p.name }}</option>
                </select>
            </div>

            <div v-if="playerA === playerB" class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-6 text-center text-sm text-slate-400">
                Alege doi jucători diferiți pentru a compara.
            </div>

            <template v-else>
                <div class="mb-5 flex items-center justify-between rounded-2xl bg-white/[0.03] px-4 py-4">
                    <div class="flex-1 text-center">
                        <p class="truncate text-sm font-semibold text-white">{{ playerById(playerA)?.name }}</p>
                        <p class="mt-1 font-display text-3xl font-extrabold text-emerald-400">{{ h2hStats.a.won }}</p>
                        <p class="text-[11px] text-slate-500">victorii</p>
                    </div>
                    <div class="px-3 text-center">
                        <p class="font-display text-xs font-bold text-slate-500">{{ h2hStats.a.goals }} - {{ h2hStats.b.goals }}</p>
                        <p class="mt-1 text-[11px] text-slate-500">{{ h2hStats.a.drawn }} egale</p>
                    </div>
                    <div class="flex-1 text-center">
                        <p class="truncate text-sm font-semibold text-white">{{ playerById(playerB)?.name }}</p>
                        <p class="mt-1 font-display text-3xl font-extrabold text-blue-400">{{ h2hStats.b.won }}</p>
                        <p class="text-[11px] text-slate-500">victorii</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <div v-for="m in h2hMatches" :key="m.id" class="flex items-center justify-between gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5">
                        <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
                            <span class="truncate text-sm font-medium text-white">{{ m.home_team.short_name }}</span>
                            <TeamCrest :team="m.home_team" size="h-7 w-7" />
                        </div>
                        <div class="shrink-0 px-2 text-center">
                            <p class="font-display text-base font-bold text-white">{{ m.home_score }} - {{ m.away_score }}</p>
                            <p class="text-[10px] text-slate-500">{{ m.championship_name }}</p>
                        </div>
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <TeamCrest :team="m.away_team" size="h-7 w-7" />
                            <span class="truncate text-sm font-medium text-white">{{ m.away_team.short_name }}</span>
                        </div>
                    </div>

                    <p v-if="h2hMatches.length === 0" class="py-6 text-center text-sm text-slate-400">
                        {{ playerById(playerA)?.name }} și {{ playerById(playerB)?.name }} nu s-au întâlnit încă.
                    </p>
                </div>
            </template>
        </div>
    </LeagueLayout>
</template>
