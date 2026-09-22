<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { CheckCircleIcon, PencilIcon, TrophyIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    cup: Object,
    rounds: Array,
});

const editing = reactive({});

function startEdit(match) {
    editing[match.id] = {
        home_score: match.home_score ?? 0,
        away_score: match.away_score ?? 0,
        home_penalties: match.home_penalties ?? 0,
        away_penalties: match.away_penalties ?? 0,
    };
}

function cancelEdit(matchId) {
    delete editing[matchId];
}

function isDraw(match) {
    const draft = editing[match.id];
    return !!draft && draft.home_score === draft.away_score;
}

function canSave(match) {
    const draft = editing[match.id];
    if (!draft) return false;
    if (!isDraw(match)) return true;
    return draft.home_penalties !== draft.away_penalties;
}

function saveScore(match) {
    const draft = editing[match.id];
    const payload = { home_score: draft.home_score, away_score: draft.away_score };
    if (isDraw(match)) {
        payload.home_penalties = draft.home_penalties;
        payload.away_penalties = draft.away_penalties;
    }
    router.put(`/league/cups/${props.cup.id}/matches/${match.id}/score`, payload, {
        preserveScroll: true,
        onSuccess: () => delete editing[match.id],
    });
}

const canPlay = (match) => !!match.home_entry_id && !!match.away_entry_id;
</script>

<template>
    <LeagueLayout :title="cup.name">
        <div class="mb-5 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ cup.name }}</h1>
                <p class="mt-1 text-sm text-slate-400">Turneu eliminatoriu &middot; {{ cup.status === 'completed' ? 'încheiată' : 'în desfășurare' }}</p>
            </div>
        </div>

        <!-- Champion banner -->
        <div
            v-if="cup.status === 'completed' && cup.winner_entry"
            class="mb-6 flex items-center gap-4 rounded-3xl border border-amber-400/30 bg-gradient-to-r from-amber-400/15 to-transparent p-5 backdrop-blur-xl"
        >
            <TrophyIcon class="h-10 w-10 shrink-0 text-amber-400" />
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-400">Campioană</p>
                <div class="mt-1 flex items-center gap-2">
                    <TeamCrest :team="cup.winner_entry.team" size="h-8 w-8" />
                    <p class="truncate font-display text-lg font-bold text-white">{{ cup.winner_entry.team?.short_name }}</p>
                    <p class="truncate text-sm text-slate-400">&middot; {{ cup.winner_entry.user?.name }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div v-for="round in rounds" :key="round.round" class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ round.label }}</p>
                <div class="space-y-2">
                    <div v-for="match in round.matches" :key="match.id" class="rounded-2xl bg-white/[0.03] px-3 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
                                <template v-if="match.home_entry">
                                    <span class="truncate text-sm font-medium" :class="match.winner_entry_id === match.home_entry_id ? 'font-bold text-white' : match.winner_entry_id ? 'text-slate-500' : 'text-white'">
                                        {{ match.home_entry.team.short_name }}
                                    </span>
                                    <TeamCrest :team="match.home_entry.team" size="h-7 w-7" />
                                </template>
                                <span v-else class="truncate text-sm text-slate-600">TBD</span>
                            </div>

                            <div class="shrink-0">
                                <div v-if="editing[match.id]" class="flex flex-col items-center gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <input v-model.number="editing[match.id].home_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                        <span class="text-slate-500">-</span>
                                        <input v-model.number="editing[match.id].away_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                        <button @click="saveScore(match)" :disabled="!canSave(match)" class="ml-1 rounded-lg bg-emerald-500/20 p-1.5 text-emerald-400 hover:bg-emerald-500/30 disabled:opacity-30">
                                            <CheckCircleIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="cancelEdit(match.id)" class="text-xs text-slate-500 hover:text-white">✕</button>
                                    </div>
                                    <div v-if="isDraw(match)" class="flex items-center gap-1.5">
                                        <span class="text-[10px] text-slate-500">pen.</span>
                                        <input v-model.number="editing[match.id].home_penalties" type="number" min="0" max="99" class="h-6 w-8 rounded-lg border border-white/10 bg-white/10 text-center text-xs text-white focus:border-amber-400 focus:ring-amber-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                        <span class="text-slate-500">-</span>
                                        <input v-model.number="editing[match.id].away_penalties" type="number" min="0" max="99" class="h-6 w-8 rounded-lg border border-white/10 bg-white/10 text-center text-xs text-white focus:border-amber-400 focus:ring-amber-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                    </div>
                                </div>
                                <button
                                    v-else-if="can.manage && canPlay(match)"
                                    @click="startEdit(match)"
                                    class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-sm font-display font-bold transition"
                                    :class="match.home_score !== null ? 'bg-white/10 text-white' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
                                >
                                    <template v-if="match.home_score !== null">
                                        {{ match.home_score }} - {{ match.away_score }}
                                        <span v-if="match.home_penalties !== null" class="text-[10px] font-normal text-slate-400">({{ match.home_penalties }}-{{ match.away_penalties }} pen.)</span>
                                    </template>
                                    <template v-else><PencilIcon class="h-3.5 w-3.5" /> scor</template>
                                </button>
                                <span v-else class="px-2.5 py-1 text-sm font-display font-bold text-white">
                                    <template v-if="match.home_score !== null">
                                        {{ match.home_score }} - {{ match.away_score }}
                                        <span v-if="match.home_penalties !== null" class="text-[10px] font-normal text-slate-400">({{ match.home_penalties }}-{{ match.away_penalties }} pen.)</span>
                                    </template>
                                    <template v-else>vs</template>
                                </span>
                            </div>

                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <template v-if="match.away_entry">
                                    <TeamCrest :team="match.away_entry.team" size="h-7 w-7" />
                                    <span class="truncate text-sm font-medium" :class="match.winner_entry_id === match.away_entry_id ? 'font-bold text-white' : match.winner_entry_id ? 'text-slate-500' : 'text-white'">
                                        {{ match.away_entry.team.short_name }}
                                    </span>
                                </template>
                                <span v-else class="truncate text-sm text-slate-600">TBD</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LeagueLayout>
</template>
