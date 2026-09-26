<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import TeamWheel from '@/Components/League/TeamWheel.vue';
import Modal from '@/Components/Modal.vue';
import { CheckIcon, SparklesIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cup: Object,
    teams: Array,
});

const toast = useToast();

const leagues = computed(() => {
    const set = new Set(props.teams.map((t) => t.league).filter(Boolean));
    return [...set].sort();
});

function groupByLeague(list) {
    const groups = new Map();
    for (const team of list) {
        const key = team.league || 'Alte echipe';
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(team);
    }
    return [...groups.entries()].map(([league, items]) => ({ league, items }));
}

const poolForm = useForm({
    team_ids: [...(props.cup.wheel_pool_team_ids || [])],
});

const takenTeamIds = computed(() => props.cup.entries.filter((e) => e.team_id).map((e) => e.team_id));

const remainingPoolTeams = computed(() =>
    props.teams.filter((t) => (poolForm.team_ids || []).includes(t.id) && !takenTeamIds.value.includes(t.id))
);

const allAssigned = computed(() => props.cup.entries.every((e) => !!e.team_id));

const unassignedEntries = computed(() => props.cup.entries.filter((e) => !e.team_id));

// When only one player and one team are left, there's no real choice — associate them automatically.
const autoAssigning = ref(false);

watch(
    [unassignedEntries, remainingPoolTeams],
    ([unassigned, pool]) => {
        if (autoAssigning.value) return;
        if (unassigned.length !== 1 || pool.length !== 1) return;

        const entry = unassigned[0];
        const team = pool[0];
        autoAssigning.value = true;

        router.put(
            `/league/cups/${props.cup.id}/entries/${entry.id}/assign`,
            { team_id: team.id },
            {
                preserveScroll: true,
                onSuccess: () => toast.success(`${entry.user.name} a primit automat ultima echipă rămasă: ${team.short_name}`),
                onFinish: () => (autoAssigning.value = false),
            }
        );
    },
    { immediate: true }
);

function togglePoolTeam(id) {
    const idx = poolForm.team_ids.indexOf(id);
    if (idx >= 0) poolForm.team_ids.splice(idx, 1);
    else poolForm.team_ids.push(id);
}

function savePool() {
    poolForm.put(`/league/cups/${props.cup.id}/wheel-pool`, { preserveScroll: true });
}

// Wheel pool builder: search + league filter
const poolSearch = ref('');
const poolLeague = ref('');
const poolFilteredTeams = computed(() => {
    const term = poolSearch.value.trim().toLowerCase();
    return props.teams.filter((t) => {
        if (poolLeague.value && t.league !== poolLeague.value) return false;
        if (!term) return true;
        return t.name.toLowerCase().includes(term) || t.short_name.toLowerCase().includes(term);
    });
});
const poolGrouped = computed(() => groupByLeague(poolFilteredTeams.value));

// Manual assignment modal: search + league filter
const manualEntry = ref(null);
const manualSearch = ref('');
const manualLeague = ref('');
const manualFilteredTeams = computed(() => {
    const term = manualSearch.value.trim().toLowerCase();
    return props.teams.filter((t) => {
        if (takenTeamIds.value.includes(t.id) && t.id !== manualEntry.value?.team_id) return false;
        if (manualLeague.value && t.league !== manualLeague.value) return false;
        if (!term) return true;
        return t.name.toLowerCase().includes(term) || t.short_name.toLowerCase().includes(term);
    });
});
const manualGrouped = computed(() => groupByLeague(manualFilteredTeams.value));

function openManual(entry) {
    manualEntry.value = entry;
    manualSearch.value = '';
    manualLeague.value = '';
}

function pickManual(team) {
    router.put(`/league/cups/${props.cup.id}/entries/${manualEntry.value.id}/assign`, { team_id: team.id }, {
        preserveScroll: true,
        onSuccess: () => (manualEntry.value = null),
    });
}

// Wheel modal
const wheelEntry = ref(null);
const wheelRef = ref(null);
const wheelSpinning = ref(false);
const wheelWinner = ref(null);
// Snapshot of the pool when the modal opens, so the slices don't shift when the page reloads after landing
// (the winner leaves the pool and, on a re-spin, the player's previous team returns to it).
const wheelPool = ref([]);

function openWheel(entry) {
    wheelEntry.value = entry;
    wheelPool.value = [...remainingPoolTeams.value];
    wheelSpinning.value = false;
    wheelWinner.value = null;
}

async function requestSpin() {
    if (wheelSpinning.value) return;
    wheelSpinning.value = true;
    wheelWinner.value = null;
    try {
        const { data } = await window.axios.post(`/league/cups/${props.cup.id}/entries/${wheelEntry.value.id}/spin`);
        wheelRef.value?.landOn(data.team);
    } catch (e) {
        wheelSpinning.value = false;
        toast.error(e?.response?.data?.message || 'Roata nu a putut fi învârtită. Încearcă din nou.');
    }
}

function onLanded(team) {
    wheelWinner.value = team;
    router.reload({
        only: ['cup'],
        onFinish: () => setTimeout(() => {
            wheelSpinning.value = false;
            wheelEntry.value = null;
            wheelWinner.value = null;
        }, 1400),
    });
}

function generate() {
    router.post(`/league/cups/${props.cup.id}/generate`);
}
</script>

<template>
    <LeagueLayout title="Selectare echipe">
        <h1 class="mb-1 font-display text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ cup.name }}</h1>
        <p class="mb-6 text-sm text-slate-400">Alege câte o echipă pentru fiecare jucător — manual sau la roată.</p>

        <!-- Wheel pool builder -->
        <div class="mb-6 rounded-3xl border border-white/10 bg-white/5 p-4 backdrop-blur-xl sm:p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display font-semibold text-white">Lista pentru roată ({{ poolForm.team_ids.length }})</h2>
                <button @click="savePool" :disabled="poolForm.processing" class="shrink-0 rounded-xl bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20 disabled:opacity-50">Salvează</button>
            </div>
            <p v-if="poolForm.errors.team_ids" class="mb-2 text-xs text-rose-400">{{ poolForm.errors.team_ids }}</p>

            <div class="mb-3 flex flex-col gap-2 sm:flex-row">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                    <input v-model="poolSearch" type="text" placeholder="Caută echipă..." class="w-full rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                </div>
                <select v-model="poolLeague" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:ring-emerald-400">
                    <option value="" class="bg-pitch-900">Toate ligile</option>
                    <option v-for="l in leagues" :key="l" :value="l" class="bg-pitch-900">{{ l }}</option>
                </select>
            </div>

            <div class="max-h-64 space-y-3 overflow-y-auto pr-1">
                <div v-for="group in poolGrouped" :key="group.league">
                    <p class="sticky top-0 mb-1.5 bg-pitch-900/80 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500 backdrop-blur">{{ group.league }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="team in group.items"
                            :key="team.id"
                            @click="togglePoolTeam(team.id)"
                            class="flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs font-medium transition"
                            :class="poolForm.team_ids.includes(team.id) ? 'border-emerald-400 bg-emerald-400/10 text-white' : 'border-white/10 bg-white/5 text-slate-400 hover:bg-white/10'"
                        >
                            <TeamCrest :team="team" size="h-4 w-4" />
                            {{ team.short_name }}
                        </button>
                    </div>
                </div>
                <p v-if="poolFilteredTeams.length === 0" class="py-4 text-center text-sm text-slate-500">Nicio echipă găsită.</p>
            </div>
        </div>

        <!-- Entries -->
        <div class="space-y-3">
            <div v-for="entry in cup.entries" :key="entry.id" class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl text-xl" :style="{ backgroundColor: (entry.user.avatar_color || '#334155') + '33', border: `1px solid ${entry.user.avatar_color || '#334155'}` }">
                        {{ entry.user.avatar_emoji || '🎮' }}
                    </div>
                    <div>
                        <p class="font-display font-semibold text-white">{{ entry.user.name }}</p>
                        <p class="text-xs text-slate-500" v-if="entry.selection_method">{{ entry.selection_method === 'wheel' ? 'ales la roată' : 'ales manual' }}</p>
                    </div>
                </div>

                <div v-if="entry.team" class="flex items-center gap-3 rounded-2xl bg-white/5 px-4 py-2">
                    <TeamCrest :team="entry.team" size="h-9 w-9" />
                    <span class="font-semibold text-white">{{ entry.team.short_name }}</span>
                    <CheckIcon class="h-5 w-5 text-emerald-400" />
                    <button @click="openManual(entry)" class="ml-2 text-xs text-slate-400 underline hover:text-white">schimbă</button>
                    <button v-if="remainingPoolTeams.length >= 2" @click="openWheel(entry)" class="inline-flex items-center gap-1 text-xs text-emerald-400 underline hover:text-emerald-300">
                        <SparklesIcon class="h-3.5 w-3.5" /> reînvârte
                    </button>
                </div>
                <div v-else-if="autoAssigning && unassignedEntries.length === 1 && unassignedEntries[0].id === entry.id" class="flex items-center gap-2 text-sm text-emerald-400">
                    <SparklesIcon class="h-4 w-4 animate-pulse" /> Se asociază automat, e ultima echipă rămasă...
                </div>
                <div v-else class="flex gap-2">
                    <button @click="openManual(entry)" class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-medium text-white hover:bg-white/10">
                        <MagnifyingGlassIcon class="h-4 w-4" /> Alege manual
                    </button>
                    <button @click="openWheel(entry)" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 px-3 py-2 text-sm font-semibold text-pitch-950">
                        <SparklesIcon class="h-4 w-4" /> Învârte roata
                    </button>
                </div>
            </div>
        </div>

        <button
            v-if="allAssigned"
            @click="generate"
            class="mt-8 w-full rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 py-4 font-display text-lg font-bold text-pitch-950 shadow-lg shadow-emerald-500/20 transition hover:brightness-110"
        >
            🏁 Generează tabloul
        </button>

        <!-- Manual pick modal -->
        <Modal :show="!!manualEntry" @close="manualEntry = null" max-width="lg">
            <div class="bg-pitch-900 p-5 sm:p-6" v-if="manualEntry">
                <h2 class="mb-4 font-display text-lg font-bold text-white">Alege echipa pentru {{ manualEntry.user.name }}</h2>

                <div class="mb-3 flex flex-col gap-2 sm:flex-row">
                    <div class="relative flex-1">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                        <input v-model="manualSearch" type="text" placeholder="Caută echipă..." class="w-full rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                    </div>
                    <select v-model="manualLeague" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:ring-emerald-400">
                        <option value="" class="bg-pitch-900">Toate ligile</option>
                        <option v-for="l in leagues" :key="l" :value="l" class="bg-pitch-900">{{ l }}</option>
                    </select>
                </div>

                <div class="max-h-96 space-y-4 overflow-y-auto pr-1">
                    <div v-for="group in manualGrouped" :key="group.league">
                        <p class="sticky top-0 mb-2 bg-pitch-900/90 py-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500 backdrop-blur">{{ group.league }}</p>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <button
                                v-for="team in group.items"
                                :key="team.id"
                                @click="pickManual(team)"
                                class="flex flex-col items-center gap-1 rounded-xl border border-white/10 bg-white/5 p-3 text-center transition hover:border-emerald-400/50 hover:bg-white/10"
                            >
                                <TeamCrest :team="team" size="h-8 w-8" />
                                <span class="truncate text-xs text-white">{{ team.short_name }}</span>
                            </button>
                        </div>
                    </div>
                    <p v-if="manualFilteredTeams.length === 0" class="py-6 text-center text-sm text-slate-500">Nicio echipă găsită.</p>
                </div>
            </div>
        </Modal>

        <!-- Wheel modal -->
        <Modal :show="!!wheelEntry" :closeable="false" max-width="md">
            <div class="bg-pitch-900 p-6 text-center" v-if="wheelEntry">
                <h2 class="mb-1 font-display text-lg font-bold text-white">Roata pentru {{ wheelEntry.user.name }}</h2>
                <p v-if="wheelEntry.team" class="mb-4 text-xs text-slate-400">Reînvârtire — {{ wheelEntry.team.short_name }} nu mai e în roată.</p>
                <div v-else class="mb-3"></div>
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

                <button v-if="!wheelSpinning" @click="wheelEntry = null" class="mt-6 text-sm text-slate-400 hover:text-white">Închide</button>
            </div>
        </Modal>
    </LeagueLayout>
</template>
