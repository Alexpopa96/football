<script setup>
import { ref, reactive, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import TeamWheel from '@/Components/League/TeamWheel.vue';
import Modal from '@/Components/Modal.vue';
import MatchBetting from '@/Components/League/MatchBetting.vue';
import { PlusIcon, TrashIcon, HandRaisedIcon, SparklesIcon, MagnifyingGlassIcon, ChevronDownIcon, XMarkIcon, CheckCircleIcon, PencilIcon, PlayIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    matches: Array,
    players: Array,
    teams: Array,
});

const showAdd = ref(false);
const liveMode = ref(false);

const form = useForm({
    home_user_id: props.players[0]?.id ?? null,
    away_user_id: props.players[1]?.id ?? props.players[0]?.id ?? null,
    home_team_id: null,
    away_team_id: null,
    home_score: 0,
    away_score: 0,
});

const canSubmit = computed(() => form.home_user_id && form.away_user_id && form.home_user_id !== form.away_user_id && form.home_team_id && form.away_team_id);

function openAdd(live = false) {
    liveMode.value = live;
    form.reset();
    form.clearErrors();
    showAdd.value = true;
}

// Manual team pick: searchable modal instead of a plain <select>.
const manualSide = ref(null); // 'home' | 'away' | null
const manualSearch = ref('');
const manualLeague = ref('');

const selectedHomeTeam = computed(() => props.teams.find((t) => t.id === form.home_team_id) ?? null);
const selectedAwayTeam = computed(() => props.teams.find((t) => t.id === form.away_team_id) ?? null);

const manualFilteredTeams = computed(() => {
    const term = manualSearch.value.trim().toLowerCase();
    return props.teams.filter((t) => {
        if (manualLeague.value && t.league !== manualLeague.value) return false;
        if (!term) return true;
        return t.name.toLowerCase().includes(term) || t.short_name.toLowerCase().includes(term);
    });
});

const manualGrouped = computed(() => {
    const groups = new Map();
    for (const team of manualFilteredTeams.value) {
        const key = team.league || 'Alte echipe';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(team);
    }
    return [...groups.entries()].map(([league, items]) => ({ league, items }));
});

function openManual(side) {
    manualSide.value = side;
    manualSearch.value = '';
    manualLeague.value = '';
}

function pickManual(team) {
    if (manualSide.value === 'home') form.home_team_id = team.id;
    else form.away_team_id = team.id;
    manualSide.value = null;
}

// Wheel: spin for a random team instead of picking manually.
const wheelSide = ref(null); // 'home' | 'away' | null
const wheelRef = ref(null);
const wheelSpinning = ref(false);
const wheelWinner = ref(null);

const leagues = computed(() => {
    const set = new Set(props.teams.map((t) => t.league).filter(Boolean));
    return [...set].sort();
});

// Custom team pool for the wheel — shared between the home/away spins, remembered while the modal is used.
// Starts empty: the user picks which teams go into the wheel.
const wheelPoolIds = ref([]);
const wheelPoolSearch = ref('');
const wheelPoolLeague = ref('');

const wheelPoolFilteredTeams = computed(() => {
    const term = wheelPoolSearch.value.trim().toLowerCase();
    return props.teams.filter((t) => {
        if (wheelPoolLeague.value && t.league !== wheelPoolLeague.value) return false;
        if (!term) return true;
        return t.name.toLowerCase().includes(term) || t.short_name.toLowerCase().includes(term);
    });
});

const wheelPoolGrouped = computed(() => {
    const groups = new Map();
    for (const team of wheelPoolFilteredTeams.value) {
        const key = team.league || 'Alte echipe';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(team);
    }
    return [...groups.entries()].map(([league, items]) => ({ league, items }));
});

function toggleWheelPoolTeam(id) {
    const idx = wheelPoolIds.value.indexOf(id);
    if (idx >= 0) {
        wheelPoolIds.value.splice(idx, 1);
    } else {
        wheelPoolIds.value.push(id);
        wheelPoolSearch.value = '';
    }
}

function selectAllWheelPool() {
    wheelPoolIds.value = wheelPoolFilteredTeams.value.map((t) => t.id);
}

function clearWheelPool() {
    wheelPoolIds.value = [];
}

const wheelPool = computed(() => {
    if (!wheelSide.value) return [];
    const otherTeamId = wheelSide.value === 'home' ? form.away_team_id : form.home_team_id;
    return props.teams.filter((t) => wheelPoolIds.value.includes(t.id) && t.id !== otherTeamId);
});

function openWheel(side) {
    wheelSide.value = side;
    wheelSpinning.value = false;
    wheelWinner.value = null;
}

function requestSpin() {
    if (wheelSpinning.value || wheelPool.value.length < 2) return;
    wheelSpinning.value = true;
    wheelWinner.value = null;
    const team = wheelPool.value[Math.floor(Math.random() * wheelPool.value.length)];
    wheelRef.value?.landOn(team);
}

function onLanded(team) {
    wheelWinner.value = team;
    if (wheelSide.value === 'home') form.home_team_id = team.id;
    else form.away_team_id = team.id;
    setTimeout(() => {
        wheelSpinning.value = false;
        wheelSide.value = null;
        wheelWinner.value = null;
    }, 1200);
}

function submit() {
    form.post(liveMode.value ? '/league/friendlies/start-live' : '/league/friendlies/store', {
        preserveScroll: true,
        onSuccess: () => (showAdd.value = false),
    });
}

function destroy(match) {
    if (! confirm('Ștergi acest meci amical?')) return;
    router.delete(`/league/friendlies/${match.id}`, { preserveScroll: true });
}

function canEditMatch(match) {
    return props.can.manage || match.created_by === usePage().props.auth.user.id;
}

function lockBetting(match) {
    router.put(`/league/friendlies/${match.id}/lock-betting`, {}, { preserveScroll: true });
}

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
    router.put(`/league/friendlies/${match.id}/score`, scores, {
        preserveScroll: true,
        onSuccess: () => delete editing[match.id],
    });
}

const formatDate = (value) =>
    value
        ? new Date(value).toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' })
        : '';
</script>

<template>
    <LeagueLayout title="Amicale">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Meciuri amicale</h1>
                <p class="mt-1 text-slate-400">Meciuri jucate în afara campionatelor, dar care contează la statistici.</p>
            </div>
            <div class="flex gap-2">
                <button @click="openAdd(true)" class="inline-flex items-center gap-2 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-2.5 font-semibold text-rose-300 hover:bg-rose-500/20">
                    <PlayIcon class="h-5 w-5" /> Meci live
                </button>
                <button @click="openAdd(false)" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2.5 font-semibold text-pitch-950">
                    <PlusIcon class="h-5 w-5" /> Meci nou
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <div v-for="m in matches" :key="m.id" class="rounded-2xl border border-white/10 bg-white/5 px-3 py-2.5 backdrop-blur-xl">
                <div v-if="m.status === 'live'" class="mb-1.5 flex items-center justify-center gap-1.5">
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-400">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span> Live
                    </span>
                    <span v-if="m.betting_locked_at" class="inline-flex items-center gap-1 rounded-full bg-white/5 px-2 py-0.5 text-[10px] font-medium text-slate-400">
                        <LockClosedIcon class="h-3 w-3" /> Pariuri blocate
                    </span>
                    <button v-else-if="canEditMatch(m)" @click="lockBetting(m)" class="inline-flex items-center gap-1 rounded-full bg-white/5 px-2.5 py-0.5 text-[10px] font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                        <LockClosedIcon class="h-3 w-3" /> Blochează pariurile
                    </button>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
                        <div class="min-w-0 text-right">
                            <p class="truncate text-sm font-medium text-white">{{ m.home_team.short_name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ m.home_user.name }}</p>
                        </div>
                        <TeamCrest :team="m.home_team" size="h-8 w-8" />
                    </div>

                    <div class="shrink-0 px-2 text-center">
                        <div v-if="editing[m.id]" class="flex items-center gap-1.5">
                            <input v-model.number="editing[m.id].home_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                            <span class="text-slate-500">-</span>
                            <input v-model.number="editing[m.id].away_score" type="number" min="0" max="99" class="h-8 w-10 rounded-lg border border-white/10 bg-white/10 text-center text-sm text-white focus:border-emerald-400 focus:ring-emerald-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                            <button @click="saveScore(m)" class="ml-1 rounded-lg bg-emerald-500/20 p-1.5 text-emerald-400 hover:bg-emerald-500/30">
                                <CheckCircleIcon class="h-4 w-4" />
                            </button>
                            <button @click="cancelEdit(m.id)" class="text-xs text-slate-500 hover:text-white">✕</button>
                        </div>
                        <button
                            v-else-if="canEditMatch(m)"
                            @click="startEdit(m)"
                            class="group inline-flex items-center gap-1.5 rounded-lg px-2 py-0.5 font-display text-lg font-bold text-white transition hover:bg-white/10"
                        >
                            <template v-if="m.home_score !== null">{{ m.home_score }} - {{ m.away_score }}</template>
                            <template v-else><PencilIcon class="h-3.5 w-3.5" /> scor</template>
                        </button>
                        <p v-else class="font-display text-lg font-bold text-white">{{ m.home_score !== null ? `${m.home_score} - ${m.away_score}` : 'vs' }}</p>
                        <p v-if="m.played_at" class="text-[10px] text-slate-500">{{ formatDate(m.played_at) }}</p>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <TeamCrest :team="m.away_team" size="h-8 w-8" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-white">{{ m.away_team.short_name }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ m.away_user.name }}</p>
                        </div>
                    </div>

                    <button
                        v-if="can.manage || m.created_by === $page.props.auth.user.id"
                        @click="destroy(m)"
                        class="shrink-0 rounded-lg p-1.5 text-slate-500 hover:bg-white/10 hover:text-rose-400"
                    >
                        <TrashIcon class="h-4 w-4" />
                    </button>
                </div>

                <MatchBetting :match="m" :store-url="`/league/friendlies/${m.id}/bets`" />
            </div>

            <div v-if="matches.length === 0" class="rounded-3xl border border-dashed border-white/15 bg-white/[0.02] p-10 text-center">
                <HandRaisedIcon class="mx-auto mb-3 h-10 w-10 text-slate-500" />
                <p class="mb-1 font-display text-lg font-semibold text-white">Niciun meci amical încă</p>
                <p class="text-sm text-slate-400">Adaugă primul meci jucat în afara unui campionat.</p>
            </div>
        </div>

        <Modal :show="showAdd" @close="showAdd = false">
            <div class="bg-pitch-900 p-6">
                <h2 class="mb-4 font-display text-lg font-bold text-white">{{ liveMode ? 'Meci live nou' : 'Meci amical nou' }}</h2>
                <p v-if="liveMode" class="-mt-2 mb-4 text-xs text-slate-400">Meciul pornește fără scor — ceilalți jucători pot paria cât e live.</p>
                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-300">Jucător acasă</label>
                            <div class="relative mt-1">
                                <select v-model.number="form.home_user_id" class="w-full appearance-none rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 pr-9 text-sm text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10">
                                    <option v-for="p in players" :key="p.id" :value="p.id" class="bg-pitch-900">{{ p.name }}</option>
                                </select>
                                <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-300">Jucător oaspete</label>
                            <div class="relative mt-1">
                                <select v-model.number="form.away_user_id" class="w-full appearance-none rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 pr-9 text-sm text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10">
                                    <option v-for="p in players" :key="p.id" :value="p.id" class="bg-pitch-900">{{ p.name }}</option>
                                </select>
                                <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            </div>
                        </div>
                    </div>
                    <p v-if="form.home_user_id === form.away_user_id" class="text-xs text-rose-400">Alege doi jucători diferiți.</p>
                    <p v-if="form.errors.home_user_id" class="text-xs text-rose-400">{{ form.errors.home_user_id }}</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-300">Echipa acasă</label>
                            <div class="mt-1 flex gap-1.5">
                                <button
                                    type="button"
                                    @click="openManual('home')"
                                    class="flex w-full items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-left text-sm shadow-inner shadow-black/20 transition hover:border-white/20 hover:bg-white/[0.08] focus:outline-none focus:ring-4 focus:ring-emerald-400/10"
                                >
                                    <TeamCrest v-if="selectedHomeTeam" :team="selectedHomeTeam" size="h-5 w-5" />
                                    <span :class="selectedHomeTeam ? 'text-white' : 'text-slate-500'">{{ selectedHomeTeam ? selectedHomeTeam.short_name : 'Alege echipa' }}</span>
                                </button>
                                <button type="button" @click="openWheel('home')" title="Dă la roată" class="shrink-0 rounded-2xl border border-white/10 bg-white/[0.04] p-2.5 text-emerald-400 shadow-inner shadow-black/20 transition hover:border-emerald-400/40 hover:bg-emerald-400/10">
                                    <SparklesIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.home_team_id" class="mt-1 text-xs text-rose-400">{{ form.errors.home_team_id }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-300">Echipa oaspete</label>
                            <div class="mt-1 flex gap-1.5">
                                <button
                                    type="button"
                                    @click="openManual('away')"
                                    class="flex w-full items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.04] px-3.5 py-2.5 text-left text-sm shadow-inner shadow-black/20 transition hover:border-white/20 hover:bg-white/[0.08] focus:outline-none focus:ring-4 focus:ring-emerald-400/10"
                                >
                                    <TeamCrest v-if="selectedAwayTeam" :team="selectedAwayTeam" size="h-5 w-5" />
                                    <span :class="selectedAwayTeam ? 'text-white' : 'text-slate-500'">{{ selectedAwayTeam ? selectedAwayTeam.short_name : 'Alege echipa' }}</span>
                                </button>
                                <button type="button" @click="openWheel('away')" title="Dă la roată" class="shrink-0 rounded-2xl border border-white/10 bg-white/[0.04] p-2.5 text-emerald-400 shadow-inner shadow-black/20 transition hover:border-emerald-400/40 hover:bg-emerald-400/10">
                                    <SparklesIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.away_team_id" class="mt-1 text-xs text-rose-400">{{ form.errors.away_team_id }}</p>
                        </div>
                    </div>

                    <div v-if="!liveMode" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-300">Scor acasă</label>
                            <input v-model.number="form.home_score" type="number" min="0" max="99" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-2.5 text-center font-display text-lg font-bold text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-300">Scor oaspete</label>
                            <input v-model.number="form.away_score" type="number" min="0" max="99" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-2.5 text-center font-display text-lg font-bold text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showAdd = false" class="rounded-xl px-4 py-2 text-sm text-slate-400 hover:text-white">Anulează</button>
                        <button type="submit" :disabled="form.processing || !canSubmit" class="rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2 text-sm font-semibold text-pitch-950 disabled:opacity-50">{{ liveMode ? 'Pornește meciul' : 'Salvează' }}</button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="!!manualSide" @close="manualSide = null" max-width="lg">
            <div class="bg-pitch-900 p-5 sm:p-6" v-if="manualSide">
                <h2 class="mb-4 font-display text-lg font-bold text-white">
                    Alege echipa {{ manualSide === 'home' ? 'acasă' : 'oaspete' }}
                </h2>

                <div class="mb-3 flex flex-col gap-2 sm:flex-row">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        <input v-model="manualSearch" type="text" autofocus placeholder="Caută echipă..." class="w-full rounded-full border border-white/10 bg-white/[0.04] py-2.5 pl-10 pr-9 text-sm text-white placeholder:text-slate-500 shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10" />
                        <button v-if="manualSearch" type="button" @click="manualSearch = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white">
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="relative sm:w-44">
                        <select v-model="manualLeague" class="w-full appearance-none rounded-full border border-white/10 bg-white/[0.04] px-4 py-2.5 pr-9 text-sm text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10">
                            <option value="" class="bg-pitch-900">Toate ligile</option>
                            <option v-for="l in leagues" :key="l" :value="l" class="bg-pitch-900">{{ l }}</option>
                        </select>
                        <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                    </div>
                </div>

                <div class="max-h-96 space-y-4 overflow-y-auto pr-1">
                    <div v-for="group in manualGrouped" :key="group.league">
                        <p class="sticky top-0 mb-2 bg-pitch-900/90 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500 backdrop-blur">{{ group.league }}</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <button
                                v-for="team in group.items"
                                :key="team.id"
                                type="button"
                                @click="pickManual(team)"
                                class="flex flex-col items-center gap-1 rounded-xl border p-3 text-center transition hover:border-emerald-400/50 hover:bg-white/10"
                                :class="(manualSide === 'home' ? form.home_team_id : form.away_team_id) === team.id ? 'border-emerald-400 bg-emerald-400/10' : 'border-white/10 bg-white/5'"
                            >
                                <TeamCrest :team="team" size="h-8 w-8" />
                                <span class="truncate text-xs text-white">{{ team.short_name }}</span>
                            </button>
                        </div>
                    </div>
                    <p v-if="manualFilteredTeams.length === 0" class="py-6 text-center text-sm text-slate-500">Nicio echipă găsită.</p>
                </div>

                <div class="mt-4 text-right">
                    <button type="button" @click="manualSide = null" class="text-sm text-slate-400 hover:text-white">Închide</button>
                </div>
            </div>
        </Modal>

        <Modal :show="!!wheelSide" :closeable="false" max-width="lg">
            <div class="bg-pitch-900 p-6 text-center" v-if="wheelSide">
                <h2 class="mb-4 font-display text-lg font-bold text-white">
                    Roata pentru echipa {{ wheelSide === 'home' ? 'acasă' : 'oaspete' }}
                </h2>

                <div v-if="!wheelSpinning" class="mb-5 rounded-2xl border border-white/10 bg-white/5 p-4 text-left">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-white">Echipe în roată ({{ wheelPoolIds.length }})</h3>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAllWheelPool" class="text-xs text-emerald-400 hover:underline">Toate</button>
                            <button type="button" @click="clearWheelPool" class="text-xs text-slate-400 hover:underline">Niciuna</button>
                        </div>
                    </div>

                    <div class="mb-3 flex flex-col gap-2 sm:flex-row">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                            <input v-model="wheelPoolSearch" type="text" placeholder="Caută echipă..." class="w-full rounded-full border border-white/10 bg-white/[0.04] py-2.5 pl-10 pr-9 text-sm text-white placeholder:text-slate-500 shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10" />
                            <button v-if="wheelPoolSearch" type="button" @click="wheelPoolSearch = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white">
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="relative sm:w-44">
                            <select v-model="wheelPoolLeague" class="w-full appearance-none rounded-full border border-white/10 bg-white/[0.04] px-4 py-2.5 pr-9 text-sm text-white shadow-inner shadow-black/20 transition focus:border-emerald-400/60 focus:bg-white/[0.07] focus:outline-none focus:ring-4 focus:ring-emerald-400/10">
                                <option value="" class="bg-pitch-900">Toate ligile</option>
                                <option v-for="l in leagues" :key="l" :value="l" class="bg-pitch-900">{{ l }}</option>
                            </select>
                            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        </div>
                    </div>

                    <div class="max-h-48 space-y-3 overflow-y-auto pr-1">
                        <div v-for="group in wheelPoolGrouped" :key="group.league">
                            <p class="sticky top-0 mb-1.5 bg-pitch-900/90 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500 backdrop-blur">{{ group.league }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="team in group.items"
                                    :key="team.id"
                                    type="button"
                                    @click="toggleWheelPoolTeam(team.id)"
                                    class="flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs font-medium transition"
                                    :class="wheelPoolIds.includes(team.id) ? 'border-emerald-400 bg-emerald-400/10 text-white' : 'border-white/10 bg-white/5 text-slate-400 hover:bg-white/10'"
                                >
                                    <TeamCrest :team="team" size="h-4 w-4" />
                                    {{ team.short_name }}
                                </button>
                            </div>
                        </div>
                        <p v-if="wheelPoolFilteredTeams.length === 0" class="py-4 text-center text-sm text-slate-500">Nicio echipă găsită.</p>
                    </div>
                </div>

                <TeamWheel ref="wheelRef" :pool="wheelPool" :disabled="wheelSpinning" @spin-click="requestSpin" @landed="onLanded" />

                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-90 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                >
                    <div v-if="wheelWinner" class="mx-auto mt-5 flex max-w-xs items-center gap-3 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3">
                        <TeamCrest :team="wheelWinner" size="h-10 w-10" />
                        <div class="text-left">
                            <p class="text-xs font-medium text-emerald-300">🎉 Echipă aleasă</p>
                            <p class="font-display font-bold text-white">{{ wheelWinner.name }}</p>
                        </div>
                    </div>
                </transition>

                <button v-if="!wheelSpinning" @click="wheelSide = null" class="mt-6 text-sm text-slate-400 hover:text-white">Închide</button>
            </div>
        </Modal>
    </LeagueLayout>
</template>
