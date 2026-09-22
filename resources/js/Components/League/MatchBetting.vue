<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import { HandRaisedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    match: Object,
    storeUrl: String,
});

const CORRECT_SCORE_MAX_GOALS = 6;
const MATCH_RESULT_ODDS = 2.0;
const CORRECT_SCORE_ODDS = 10.0;

const correctScoreOptions = [];
for (let home = 0; home <= CORRECT_SCORE_MAX_GOALS; home++) {
    for (let away = 0; away <= CORRECT_SCORE_MAX_GOALS; away++) {
        const value = `${home}-${away}`;
        correctScoreOptions.push({ value, label: value, odds: CORRECT_SCORE_ODDS });
    }
}
correctScoreOptions.push({ value: 'other', label: 'Altul', odds: CORRECT_SCORE_ODDS });

const MARKETS = [
    {
        key: 'match_result',
        label: '1X2',
        options: [
            { value: 'home', label: 'Gazdă', odds: MATCH_RESULT_ODDS },
            { value: 'draw', label: 'Egal', odds: MATCH_RESULT_ODDS },
            { value: 'away', label: 'Oaspete', odds: MATCH_RESULT_ODDS },
        ],
    },
    {
        key: 'correct_score',
        label: 'Scor exact (0-0 → 6-6)',
        options: correctScoreOptions,
    },
];

const showModal = ref(false);
const placing = ref(false);
const selectedMarket = ref(null);
const selectedSelection = ref(null);
const stake = ref(1);

const page = usePage();
const balance = computed(() => page.props.auth.user.bet_balance ?? 0);
const currentBet = computed(() => props.match.my_bets?.[0] ?? null);

// While editing an existing bet, its staked points are still "available" since re-confirming refunds and re-stakes them.
const availableForStake = computed(() => balance.value + (currentBet.value?.stake ?? 0));

const selectedOdds = computed(() => {
    if (!selectedMarket.value || !selectedSelection.value) return null;
    return MARKETS.find((m) => m.key === selectedMarket.value)?.options.find((o) => o.value === selectedSelection.value)?.odds ?? null;
});

const potentialPayout = computed(() => (selectedOdds.value ? Math.round(stake.value * selectedOdds.value) : 0));

const canConfirm = computed(
    () => selectedMarket.value && selectedSelection.value && stake.value >= 1 && stake.value <= availableForStake.value && !placing.value
);

watch(showModal, (open) => {
    if (!open) return;

    if (currentBet.value) {
        selectedMarket.value = currentBet.value.market;
        selectedSelection.value = currentBet.value.selection;
        stake.value = currentBet.value.stake;
    } else {
        selectedMarket.value = null;
        selectedSelection.value = null;
        stake.value = Math.min(5, availableForStake.value) || 1;
    }
});

function isSelectedOption(market, selection) {
    return selectedMarket.value === market && selectedSelection.value === selection;
}

function selectOption(market, selection) {
    selectedMarket.value = market;
    selectedSelection.value = selection;
}

function confirm() {
    placing.value = true;
    router.post(
        props.storeUrl,
        { market: selectedMarket.value, selection: selectedSelection.value, stake: stake.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onFinish: () => {
                placing.value = false;
            },
        }
    );
}

function remove() {
    placing.value = true;
    router.delete(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        },
        onFinish: () => {
            placing.value = false;
        },
    });
}

function marketLabel(market) {
    return MARKETS.find((m) => m.key === market)?.label ?? market;
}

function selectionLabel(market, value) {
    return MARKETS.find((m) => m.key === market)?.options.find((o) => o.value === value)?.label ?? value;
}

const pointsClass = (points) => (points > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400');
</script>

<template>
    <div class="mt-2 border-t border-white/5 pt-2">
        <div v-if="match.can_bet" class="flex justify-center">
            <button
                type="button"
                @click="showModal = true"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-medium transition"
                :class="currentBet ? 'bg-violet-500/10 text-violet-300' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'"
            >
                <HandRaisedIcon class="h-3 w-3" />
                <template v-if="currentBet">
                    Pariul tău: {{ marketLabel(currentBet.market) }} {{ selectionLabel(currentBet.market, currentBet.selection) }} &middot;
                    {{ currentBet.stake }}p @ {{ Number(currentBet.odds).toFixed(2) }}x
                </template>
                <template v-else>Pariază</template>
            </button>
        </div>

        <div v-else-if="match.status === 'finished' && match.bets?.length" class="flex flex-wrap items-center justify-center gap-1.5">
            <span v-for="bet in match.bets" :key="bet.id" class="rounded-full px-2 py-0.5 text-[11px] font-medium" :class="pointsClass(bet.points_awarded)">
                {{ bet.user?.name }}: {{ marketLabel(bet.market) }} {{ selectionLabel(bet.market, bet.selection) }} ({{ bet.stake }}p @
                {{ Number(bet.odds).toFixed(2) }}x) &middot; {{ bet.points_awarded > 0 ? '+' : '' }}{{ bet.points_awarded }}p
            </span>
        </div>

        <Modal :show="showModal" max-width="sm" @close="showModal = false">
            <div class="bg-pitch-900 p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold text-white">Plasează un pariu</h2>
                    <span class="rounded-full bg-white/5 px-2.5 py-1 text-xs font-semibold text-white">Sold: {{ availableForStake }}p</span>
                </div>
                <p class="mb-4 text-xs text-slate-400">
                    Un singur pariu activ pe acest meci &middot; 1X2 {{ MATCH_RESULT_ODDS.toFixed(2) }}x, scor exact {{ CORRECT_SCORE_ODDS.toFixed(2) }}x. Dacă pierzi, îți pierzi miza.
                </p>

                <div class="max-h-[45vh] space-y-4 overflow-y-auto pr-1">
                    <div>
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">1X2</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="opt in MARKETS[0].options"
                                :key="opt.value"
                                type="button"
                                @click="selectOption('match_result', opt.value)"
                                class="rounded-xl px-3 py-2 text-sm font-medium transition"
                                :class="
                                    isSelectedOption('match_result', opt.value)
                                        ? 'bg-violet-500/20 text-violet-300 ring-1 ring-violet-400/40'
                                        : 'bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white'
                                "
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Scor exact (0-0 → 6-6)</p>
                        <div class="grid grid-cols-7 gap-1">
                            <button
                                v-for="opt in correctScoreOptions.filter((o) => o.value !== 'other')"
                                :key="opt.value"
                                type="button"
                                @click="selectOption('correct_score', opt.value)"
                                class="rounded-lg py-1.5 text-[11px] font-semibold transition"
                                :class="
                                    isSelectedOption('correct_score', opt.value)
                                        ? 'bg-violet-500/20 text-violet-300 ring-1 ring-violet-400/40'
                                        : 'bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white'
                                "
                            >
                                {{ opt.label }}
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="selectOption('correct_score', 'other')"
                            class="mt-1.5 w-full rounded-lg py-1.5 text-xs font-medium transition"
                            :class="
                                isSelectedOption('correct_score', 'other')
                                    ? 'bg-violet-500/20 text-violet-300 ring-1 ring-violet-400/40'
                                    : 'bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white'
                            "
                        >
                            Alt scor
                        </button>
                    </div>
                </div>

                <div v-if="selectedMarket" class="mt-4 space-y-2 border-t border-white/5 pt-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-slate-300">Puncte puse în joc</label>
                        <span class="text-[11px] text-slate-500">Cotă {{ selectedOdds.toFixed(2) }}x</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <input
                            v-model.number="stake"
                            type="number"
                            min="1"
                            :max="availableForStake"
                            class="w-20 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2 text-center text-sm text-white focus:border-violet-400 focus:ring-violet-400 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                        />
                        <button
                            v-for="preset in [5, 10]"
                            :key="preset"
                            type="button"
                            :disabled="preset > availableForStake"
                            @click="stake = preset"
                            class="rounded-lg bg-white/5 px-2.5 py-1.5 text-xs text-slate-300 hover:bg-white/10 disabled:opacity-30"
                        >
                            {{ preset }}
                        </button>
                        <button type="button" @click="stake = availableForStake" class="rounded-lg bg-white/5 px-2.5 py-1.5 text-xs text-amber-300 hover:bg-white/10">
                            All-in
                        </button>
                    </div>
                    <p class="text-xs text-slate-400">Câștig posibil: <span class="font-semibold text-emerald-400">{{ potentialPayout }}p</span></p>
                    <p v-if="stake > availableForStake || stake < 1" class="text-xs text-rose-400">Introdu o miză validă (1–{{ availableForStake }}).</p>
                </div>

                <div class="mt-5 flex items-center justify-between">
                    <button v-if="currentBet" type="button" :disabled="placing" @click="remove" class="text-sm text-rose-400 hover:text-rose-300 disabled:opacity-50">
                        Anulează pariul
                    </button>
                    <span v-else></span>
                    <div class="flex gap-2">
                        <button type="button" @click="showModal = false" class="rounded-xl px-4 py-2 text-sm text-slate-400 hover:text-white">Închide</button>
                        <button
                            type="button"
                            :disabled="!canConfirm"
                            @click="confirm"
                            class="rounded-xl bg-gradient-to-r from-violet-400 to-fuchsia-500 px-4 py-2 text-sm font-semibold text-pitch-950 disabled:opacity-50"
                        >
                            Plasează
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </div>
</template>
