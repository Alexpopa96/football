<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import TeamCrest from '@/Components/League/TeamCrest.vue';

const props = defineProps({
    pool: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['spin-click', 'landed']);

const wheelEl = ref(null);
const rotation = ref(0);
const spinning = ref(false);
const justLanded = ref(false);
const transitionDuration = 4.5; // seconds
let cleanupTimer = null;
let transitionHandler = null;

const sliceAngle = computed(() => (props.pool.length ? 360 / props.pool.length : 0));

function colorFor(team, index) {
    if (team.primary_color) return team.primary_color;
    let hash = 0;
    const label = team.name || String(index);
    for (let i = 0; i < label.length; i++) hash = label.charCodeAt(i) + ((hash << 5) - hash);
    return `hsl(${Math.abs(hash) % 360}, 62%, 40%)`;
}

const conicGradient = computed(() => {
    if (!props.pool.length) return 'transparent';
    const stops = props.pool.map((team, i) => {
        const from = i * sliceAngle.value;
        const to = from + sliceAngle.value;
        return `${colorFor(team, i)} ${from}deg ${to}deg`;
    });
    return `conic-gradient(from 0deg, ${stops.join(', ')})`;
});

const iconPositions = computed(() => props.pool.map((team, i) => {
    const centerDeg = i * sliceAngle.value + sliceAngle.value / 2;
    const rad = ((centerDeg - 90) * Math.PI) / 180;
    const radius = 37;
    const x = 50 + radius * Math.cos(rad);
    const y = 50 + radius * Math.sin(rad);
    return { team, x, y };
}));

const dividers = computed(() => props.pool.map((_, i) => i * sliceAngle.value));

// decorative rim lights
const lights = computed(() => {
    const count = 20;
    return Array.from({ length: count }, (_, i) => ({
        angle: (360 / count) * i,
        delay: (i % 5) * 0.12,
    }));
});

function requestSpin() {
    if (spinning.value || props.disabled || props.pool.length < 2) return;
    justLanded.value = false;
    spinning.value = true;
    emit('spin-click');
}

function clearPending() {
    if (cleanupTimer) {
        clearTimeout(cleanupTimer);
        cleanupTimer = null;
    }
    if (transitionHandler && wheelEl.value) {
        wheelEl.value.removeEventListener('transitionend', transitionHandler);
    }
    transitionHandler = null;
}

function landOn(team) {
    const index = props.pool.findIndex((t) => t.id === team.id);
    if (index === -1) {
        // team isn't in the currently rendered pool (shouldn't normally happen) — don't leave the UI stuck.
        spinning.value = false;
        emit('landed', team);
        return;
    }

    const sliceCenter = index * sliceAngle.value + sliceAngle.value / 2;
    const targetMod = (360 - sliceCenter + 360) % 360;
    const currentMod = ((rotation.value % 360) + 360) % 360;
    const extraSpins = 6 + Math.floor(Math.random() * 2);
    const delta = ((targetMod - currentMod) + 360) % 360;
    const nextRotation = rotation.value + extraSpins * 360 + delta;

    clearPending();

    const finish = () => {
        clearPending();
        spinning.value = false;
        justLanded.value = true;
        emit('landed', team);
    };

    transitionHandler = (e) => {
        if (e.target === wheelEl.value && e.propertyName === 'transform') finish();
    };
    wheelEl.value?.addEventListener('transitionend', transitionHandler);
    // Safety net in case the transitionend event never fires (e.g. tab backgrounded).
    cleanupTimer = setTimeout(finish, transitionDuration * 1000 + 600);

    // Double rAF guarantees the browser paints the "transition enabled" frame
    // before we change the rotation, so the animation always plays.
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            rotation.value = nextRotation;
        });
    });
}

onBeforeUnmount(clearPending);

defineExpose({ landOn });
</script>

<template>
    <div class="flex flex-col items-center gap-6">
        <div class="relative h-72 w-72 sm:h-80 sm:w-80">
            <!-- ambient glow -->
            <div
                class="absolute inset-0 rounded-full blur-2xl transition-opacity duration-500"
                :class="spinning ? 'opacity-70' : 'opacity-40'"
                style="background: radial-gradient(circle, rgba(52,211,153,0.45), rgba(59,130,246,0.25) 55%, transparent 75%);"
            ></div>

            <!-- rim lights -->
            <div class="absolute inset-0">
                <span
                    v-for="(light, i) in lights"
                    :key="i"
                    class="absolute h-1.5 w-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300 shadow-[0_0_6px_2px_rgba(252,211,77,0.7)]"
                    :class="spinning ? 'animate-pulse' : ''"
                    :style="{
                        left: `${50 + 47 * Math.cos(((light.angle - 90) * Math.PI) / 180)}%`,
                        top: `${50 + 47 * Math.sin(((light.angle - 90) * Math.PI) / 180)}%`,
                        animationDelay: `${light.delay}s`,
                        animationDuration: spinning ? '0.5s' : '2s',
                    }"
                ></span>
            </div>

            <!-- pointer -->
            <div class="absolute left-1/2 top-1 z-20 -translate-x-1/2">
                <div class="h-0 w-0 border-x-[11px] border-t-[18px] border-x-transparent border-t-amber-400 drop-shadow-[0_2px_4px_rgba(0,0,0,0.4)]"></div>
                <div class="mx-auto -mt-1 h-2.5 w-2.5 rounded-full bg-amber-400 shadow-[0_0_8px_2px_rgba(252,211,77,0.7)]"></div>
            </div>

            <!-- wheel -->
            <div
                ref="wheelEl"
                class="absolute inset-3 rounded-full shadow-[0_0_0_6px_rgba(255,255,255,0.08),0_20px_50px_-10px_rgba(0,0,0,0.6)] ring-2 ring-white/20"
                :style="{
                    background: conicGradient,
                    transform: `rotate(${rotation}deg)`,
                    transition: `transform ${transitionDuration}s cubic-bezier(0.12, 0.66, 0.1, 1)`,
                }"
            >
                <!-- slice dividers -->
                <div
                    v-for="(deg, i) in dividers"
                    :key="'div-' + i"
                    class="absolute left-1/2 top-1/2 h-1/2 w-px origin-top bg-white/15"
                    :style="{ transform: `rotate(${deg}deg)` }"
                ></div>

                <!-- crest badges -->
                <div
                    v-for="(pos, i) in iconPositions"
                    :key="pool[i].id"
                    class="absolute flex h-9 w-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-white p-1 shadow-lg ring-1 ring-black/10"
                    :style="{ left: pos.x + '%', top: pos.y + '%' }"
                >
                    <TeamCrest :team="pos.team" size="h-6 w-6" />
                </div>

                <!-- inner ring -->
                <div class="pointer-events-none absolute inset-6 rounded-full ring-1 ring-white/10"></div>
            </div>

            <!-- center hub -->
            <button
                type="button"
                @click="requestSpin"
                :disabled="spinning || disabled || pool.length < 2"
                class="absolute left-1/2 top-1/2 z-10 flex h-[4.5rem] w-[4.5rem] -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center gap-0.5 rounded-full border border-white/30 bg-gradient-to-br from-emerald-400 to-blue-500 font-display text-xs font-extrabold uppercase tracking-wide text-pitch-950 shadow-[0_0_0_6px_rgba(5,7,13,0.9),0_8px_20px_rgba(16,185,129,0.35)] transition active:scale-95 disabled:opacity-60"
                :class="!spinning && !disabled && pool.length >= 2 ? 'animate-[pulse-ring_2.4s_ease-in-out_infinite]' : ''"
            >
                <svg v-if="spinning" class="h-5 w-5 animate-spin text-pitch-950/70" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4z" />
                </svg>
                <template v-else>SPIN</template>
            </button>
        </div>

        <p v-if="pool.length < 2" class="text-sm text-slate-500">Alege cel puțin 2 echipe pentru roată.</p>
    </div>
</template>

<style scoped>
@keyframes pulse-ring {
    0%, 100% { box-shadow: 0 0 0 6px rgba(5,7,13,0.9), 0 8px 20px rgba(16,185,129,0.35); }
    50% { box-shadow: 0 0 0 10px rgba(5,7,13,0.9), 0 8px 28px rgba(16,185,129,0.55); }
}
</style>
