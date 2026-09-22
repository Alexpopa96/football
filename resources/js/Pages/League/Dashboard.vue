<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { TrophyIcon, ShieldCheckIcon, PlusIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    active: Object,
    standings: Array,
    championshipsCount: Number,
    teamsCount: Number,
});

const podium = computed(() => (props.standings || []).slice(0, 4));
</script>

<template>
    <LeagueLayout title="Acasă">
        <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Bun venit! 👋</h1>
                <p class="mt-1 text-slate-400">Iată starea ligii voastre de FIFA.</p>
            </div>
            <Link
                v-if="can.manage"
                href="/league/championships/create"
                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2.5 font-semibold text-pitch-950 shadow-lg shadow-emerald-500/20 transition hover:brightness-110"
            >
                <PlusIcon class="h-5 w-5" /> Campionat nou
            </Link>
        </div>

        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-2">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <TrophyIcon class="mb-2 h-6 w-6 text-emerald-400" />
                <p class="font-display text-2xl font-bold text-white">{{ championshipsCount }}</p>
                <p class="text-sm text-slate-400">campionate create</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl">
                <ShieldCheckIcon class="mb-2 h-6 w-6 text-blue-400" />
                <p class="font-display text-2xl font-bold text-white">{{ teamsCount }}</p>
                <p class="text-sm text-slate-400">echipe disponibile</p>
            </div>
        </div>

        <div v-if="active" class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur-xl">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-emerald-400">Campionat activ</p>
                    <h2 class="font-display text-xl font-bold text-white">{{ active.name }}</h2>
                </div>
                <Link :href="active.status === 'selecting_teams' ? `/league/championships/${active.id}/select-teams` : `/league/championships/${active.id}`" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-400 hover:text-emerald-300">
                    Vezi tot <ArrowRightIcon class="h-4 w-4" />
                </Link>
            </div>

            <div v-if="standings" class="space-y-2">
                <div
                    v-for="row in podium"
                    :key="row.entry_id"
                    class="flex items-center gap-3 rounded-2xl px-3 py-2.5"
                    :class="row.position === 1 ? 'bg-gradient-to-r from-amber-400/10 to-transparent ring-1 ring-amber-400/30' : 'bg-white/[0.03]'"
                >
                    <span class="w-5 text-center font-display text-sm font-bold text-slate-400">{{ row.position }}</span>
                    <TeamCrest :team="row.team" size="h-8 w-8" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ row.team?.short_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ row.user?.name }}</p>
                    </div>
                    <span class="font-display text-lg font-bold text-white">{{ row.points }}</span>
                    <span class="text-xs text-slate-500">pct</span>
                </div>
            </div>
            <p v-else class="text-sm text-slate-400">Selectarea echipelor este în desfășurare.</p>
        </div>

        <div v-else class="rounded-3xl border border-dashed border-white/15 bg-white/[0.02] p-10 text-center">
            <p class="mb-3 text-5xl">🏆</p>
            <p class="mb-1 font-display text-lg font-semibold text-white">Niciun campionat activ</p>
            <p class="mb-5 text-sm text-slate-400">Creează un campionat nou și porniți un clasament tur-retur.</p>
            <Link v-if="can.manage" href="/league/championships/create" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2.5 font-semibold text-pitch-950">
                <PlusIcon class="h-5 w-5" /> Campionat nou
            </Link>
        </div>
    </LeagueLayout>
</template>
