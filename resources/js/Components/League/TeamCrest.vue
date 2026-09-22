<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    team: {
        type: Object,
        default: null,
    },
    size: {
        type: String,
        default: 'h-10 w-10',
    },
});

const failed = ref(false);

const initials = computed(() => {
    const label = props.team?.short_name || props.team?.name || '?';
    return label
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
});

const fallbackColor = computed(() => {
    if (props.team?.primary_color) return props.team.primary_color;
    const label = props.team?.name || 'x';
    let hash = 0;
    for (let i = 0; i < label.length; i++) {
        hash = label.charCodeAt(i) + ((hash << 5) - hash);
    }
    const hue = Math.abs(hash) % 360;
    return `hsl(${hue}, 65%, 45%)`;
});
</script>

<template>
    <img
        v-if="team?.crest_url && !failed"
        :src="team.crest_url"
        :alt="team.name"
        :class="size"
        class="object-contain drop-shadow-[0_0_6px_rgba(0,0,0,0.35)]"
        loading="lazy"
        @error="failed = true"
    />
    <div
        v-else
        :class="size"
        class="flex items-center justify-center rounded-full font-display font-bold text-white shadow-inner"
        :style="{ backgroundColor: fallbackColor }"
    >
        <span class="text-[0.6em]">{{ initials || '?' }}</span>
    </div>
</template>
