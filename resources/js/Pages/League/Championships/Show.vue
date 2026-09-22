<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { CheckCircleIcon, PencilIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    championship: Object,
    standings: Array,
    rounds: Array,
});

const editing = reactive({});

function startEdit(match) {
    editing[match.id] = {
        home_score: match.home_score ?? 0,
        away_score: match.away_score ?? 0,
    };
}

function cancelEdit(matchId) {
    delete editing[matchId];
}

function saveScore(match) {
    const scores = editing[match.id];
    router.put(`/league/championships/${props.championship.id}/matches/${match.id}/score`, scores, {
        preserveScroll: true,
        onSuccess: () => delete editing[match.id],
    });
}

const legLabel = (leg) => (leg === 'tur' ? 'Tur' : 'Retur');
</script>

<template>
    <LeagueLayout :title="championship.name">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ championship.name }}</h1>
                <p class="mt-1 text-sm text-slate-400">Clasament tur-retur &middot; {{ championship.status === 'completed' ? 'încheiat' : 'în desfășurare' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <!-- Standings -->
            <div>
                <!-- Mobile: compact card list -->
                <div class="space-y-2 sm:hidden">
                    <div
                        v-for="row in standings"
                        :key="row.entry_id"
                        class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5 backdrop-blur-xl"
                        :class="row.position === 1 ? 'bg-gradient-to-r from-amber-400/10 to-transparent ring-1 ring-amber-400/20' : ''"
                    >
                        <span class="w-4 text-center font-display font-bold" :class="row.position === 1 ? 'text-amber-400' : 'text-slate-400'">{{ row.position }}</span>
                        <TeamCrest :team="row.team" size="h-8 w-8" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white">{{ row.team?.short_name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ row.user?.name }} &middot; {{ row.played }}J</p>
                        </div>
                        <span class="text-xs font-medium" :class="row.goal_difference > 0 ? 'text-emerald-400' : row.goal_difference < 0 ? 'text-rose-400' : 'text-slate-400'">
                            {{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}
                        </span>
                        <span class="font-display text-lg font-extrabold text-white">{{ row.points }}</span>
                    </div>
                </div>

                <!-- sm+: full table -->
                <div class="hidden overflow-hidden rounded-3xl border border-white/10 bg-white/5 backdrop-blur-xl sm:block">
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-left text-[11px] uppercase tracking-wider text-slate-500">
                                <th class="px-3 py-3 font-medium">#</th>
                                <th class="px-2 py-3 font-medium">Echipă</th>
                                <th class="px-2 py-3 text-center font-medium">J</th>
                                <th class="px-2 py-3 text-center font-medium">V</th>
                                <th class="px-2 py-3 text-center font-medium">E</th>
                                <th class="px-2 py-3 text-center font-medium">Î</th>
                                <th class="px-2 py-3 text-center font-medium">GM</th>
                                <th class="px-2 py-3 text-center font-medium">GP</th>
                                <th class="px-2 py-3 text-center font-medium">GA</th>
                                <th class="px-3 py-3 text-center font-medium">Pct</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in standings"
                                :key="row.entry_id"
                                class="border-b border-white/5 last:border-0"
                                :class="row.position === 1 ? 'bg-gradient-to-r from-amber-400/10 to-transparent' : ''"
                            >
                                <td class="px-3 py-3 font-display font-bold" :class="row.position === 1 ? 'text-amber-400' : 'text-slate-400'">{{ row.position }}</td>
                                <td class="px-2 py-3">
                                    <div class="flex items-center gap-2">
                                        <TeamCrest :team="row.team" size="h-7 w-7" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white">{{ row.team?.short_name }}</p>
                                            <p class="truncate text-[11px] text-slate-500">{{ row.user?.name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.played }}</td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.won }}</td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.drawn }}</td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.lost }}</td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.goals_for }}</td>
                                <td class="px-2 py-3 text-center text-slate-300">{{ row.goals_against }}</td>
                                <td class="px-2 py-3 text-center font-medium" :class="row.goal_difference > 0 ? 'text-emerald-400' : row.goal_difference < 0 ? 'text-rose-400' : 'text-slate-400'">
                                    {{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}
                                </td>
                                <td class="px-3 py-3 text-center font-display text-base font-extrabold text-white">{{ row.points }}</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- Fixtures -->
            <div class="space-y-4">
                <div v-for="round in rounds" :key="round.round" class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ legLabel(round.leg) }} &middot; Runda {{ round.round }}</p>
                    <div class="space-y-2">
                        <div v-for="match in round.matches" :key="match.id" class="flex items-center justify-between gap-3 rounded-2xl bg-white/[0.03] px-3 py-2.5">
                            <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
                                <span class="truncate text-sm font-medium text-white">{{ match.home_entry.team.short_name }}</span>
                                <TeamCrest :team="match.home_entry.team" size="h-7 w-7" />
                            </div>

                            <div class="shrink-0">
                                <div v-if="editing[match.id]" class="flex items-center gap-1.5">
                                    <input v-model.number="editing[match.id].home_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                    <span class="text-slate-500">-</span>
                                    <input v-model.number="editing[match.id].away_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                    <button @click="saveScore(match)" class="ml-1 rounded-lg bg-emerald-500/20 p-1.5 text-emerald-400 hover:bg-emerald-500/30">
                                        <CheckCircleIcon class="h-4 w-4" />
                                    </button>
                                    <button @click="cancelEdit(match.id)" class="text-xs text-slate-500 hover:text-white">✕</button>
                                </div>
                                <button
                                    v-else-if="can.manage"
                                    @click="startEdit(match)"
                                    class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-display font-bold transition"
                                    :class="match.home_score !== null ? 'bg-white/10 text-white' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
                                >
                                    <template v-if="match.home_score !== null">{{ match.home_score }} - {{ match.away_score }}</template>
                                    <template v-else><PencilIcon class="h-3.5 w-3.5" /> scor</template>
                                </button>
                                <span v-else class="px-2.5 py-1 text-sm font-display font-bold text-white">
                                    {{ match.home_score !== null ? `${match.home_score} - ${match.away_score}` : 'vs' }}
                                </span>
                            </div>

                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <TeamCrest :team="match.away_entry.team" size="h-7 w-7" />
                                <span class="truncate text-sm font-medium text-white">{{ match.away_entry.team.short_name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
