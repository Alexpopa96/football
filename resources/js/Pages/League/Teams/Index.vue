<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { throttle } from 'lodash';
import LeagueLayout from '@/Layouts/LeagueLayout.vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';
import Modal from '@/Components/Modal.vue';
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    can: Object,
    teams: Array,
    leagues: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const league = ref(props.filters.league || '');
const showAdd = ref(false);

watch([search, league], throttle(() => {
    router.get('/league/teams', { search: search.value || undefined, league: league.value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
}, 300));

const form = useForm({
    name: '',
    short_name: '',
    country: '',
    league: '',
    crest_url: '',
});

function submit() {
    form.post('/league/teams/store', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAdd.value = false;
        },
    });
}

function toggleActive(team) {
    router.put(`/league/teams/${team.id}/update`, {
        name: team.name,
        short_name: team.short_name,
        country: team.country,
        league: team.league,
        primary_color: team.primary_color,
        crest_url: team.crest_url,
        is_active: !team.is_active,
    }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <LeagueLayout title="Echipe">
        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="font-display text-3xl font-extrabold tracking-tight text-white">Echipe</h1>
                <p class="mt-1 text-slate-400">Catalogul de echipe pentru selecție și roată.</p>
            </div>
            <button v-if="can.manage" @click="showAdd = true" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2.5 font-semibold text-pitch-950">
                <PlusIcon class="h-5 w-5" /> Adaugă echipă
            </button>
        </div>

        <div class="mb-6 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input v-model="search" type="text" placeholder="Caută echipă..." class="w-full rounded-2xl border border-white/10 bg-white/5 py-2.5 pl-9 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
            </div>
            <select v-model="league" class="rounded-2xl border border-white/10 bg-white/5 py-2.5 px-4 text-sm text-white focus:border-emerald-400 focus:ring-emerald-400">
                <option value="" class="bg-pitch-900">Toate ligile</option>
                <option v-for="l in leagues" :key="l" :value="l" class="bg-pitch-900">{{ l }}</option>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <div
                v-for="team in teams"
                :key="team.id"
                class="group relative flex flex-col items-center gap-2 rounded-2xl border border-white/10 bg-white/5 p-4 text-center backdrop-blur-xl transition hover:border-white/20 hover:bg-white/10"
                :class="{ 'opacity-40': !team.is_active }"
            >
                <TeamCrest :team="team" size="h-12 w-12" />
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-white">{{ team.short_name }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ team.league }}</p>
                </div>
                <button
                    v-if="can.manage"
                    @click="toggleActive(team)"
                    class="absolute right-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-semibold opacity-0 transition group-hover:opacity-100"
                    :class="team.is_active ? 'bg-rose-500/20 text-rose-300' : 'bg-emerald-500/20 text-emerald-300'"
                >
                    {{ team.is_active ? 'Dezactivează' : 'Activează' }}
                </button>
            </div>

            <p v-if="teams.length === 0" class="col-span-full py-10 text-center text-sm text-slate-500">Nicio echipă găsită.</p>
        </div>

        <Modal :show="showAdd" @close="showAdd = false">
            <div class="bg-pitch-900 p-6">
                <h2 class="mb-4 font-display text-lg font-bold text-white">Adaugă echipă</h2>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="text-xs font-medium text-slate-300">Nume complet</label>
                        <input v-model="form.name" type="text" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-400">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-300">Nume scurt</label>
                        <input v-model="form.short_name" type="text" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                        <p v-if="form.errors.short_name" class="mt-1 text-xs text-rose-400">{{ form.errors.short_name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-slate-300">Țară</label>
                            <input v-model="form.country" type="text" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-300">Ligă</label>
                            <input v-model="form.league" type="text" class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-300">URL siglă</label>
                        <input v-model="form.crest_url" type="text" placeholder="https://..." class="mt-1 w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400" />
                        <p v-if="form.errors.crest_url" class="mt-1 text-xs text-rose-400">{{ form.errors.crest_url }}</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showAdd = false" class="rounded-xl px-4 py-2 text-sm text-slate-400 hover:text-white">Anulează</button>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-gradient-to-r from-emerald-400 to-blue-500 px-4 py-2 text-sm font-semibold text-pitch-950 disabled:opacity-50">Salvează</button>
                    </div>
                </form>
            </div>
        </Modal>
    </LeagueLayout>
</template>
