<script setup>
import { Link } from '@inertiajs/vue3';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import { PlusIcon, GiftIcon } from '@heroicons/vue/24/outline';

defineProps({
    can: Object,
    cups: Array,
});

const statusMeta = {
    draft: { label: 'Ciornă', class: 'bg-slate-500/20 text-slate-300' },
    selecting_teams: { label: 'Selectare echipe', class: 'bg-amber-500/20 text-amber-300' },
    in_progress: { label: 'În desfășurare', class: 'bg-emerald-500/20 text-emerald-300' },
    completed: { label: 'Încheiată', class: 'bg-blue-500/20 text-blue-300' },
};

const targetHref = (c) => (c.status === 'selecting_teams' ? `/league/cups/${c.id}/select-teams` : `/league/cups/${c.id}`);
</script>

<template>
    <LeagueLayout title="Cupă">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Cupă</h1>
                <p class="mt-1 text-slate-400">Turnee eliminatorii, cu semifinale și finală.</p>
            </div>
            <Link v-if="can.manage" href="/league/cups/create" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2.5 font-semibold text-pitch-950">
                <PlusIcon class="h-5 w-5" /> Cupă nouă
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="c in cups" :key="c.id" :href="targetHref(c)" class="group rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-xl transition hover:-translate-y-1 hover:border-white/20 hover:bg-white/10">
                <div class="mb-3 flex items-center justify-between">
                    <GiftIcon class="h-6 w-6 text-amber-400" />
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusMeta[c.status]?.class">{{ statusMeta[c.status]?.label }}</span>
                </div>
                <h3 class="mb-3 font-display text-lg font-bold text-white">{{ c.name }}</h3>
                <div class="flex -space-x-2">
                    <div v-for="entry in c.entries" :key="entry.id" class="rounded-full ring-2 ring-pitch-900">
                        <TeamCrest :team="entry.team" size="h-8 w-8" />
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ c.matches_count }} meciuri</p>
            </Link>

            <div v-if="cups.length === 0" class="col-span-full rounded-3xl border border-dashed border-white/15 bg-white/[0.02] p-10 text-center">
                <p class="mb-3 text-5xl">🏆</p>
                <p class="mb-1 font-display text-lg font-semibold text-white">Nicio cupă încă</p>
                <p class="text-sm text-slate-400">Creează prima cupă pentru a începe.</p>
            </div>
        </div>
    </LeagueLayout>
</template>
