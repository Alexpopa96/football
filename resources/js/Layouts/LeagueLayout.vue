<script setup>
import { computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    HomeIcon,
    TrophyIcon,
    GiftIcon,
    ShieldCheckIcon,
    ChartBarIcon,
    HandRaisedIcon,
    ArrowRightStartOnRectangleIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeIconSolid,
    TrophyIcon as TrophyIconSolid,
    GiftIcon as GiftIconSolid,
    ShieldCheckIcon as ShieldCheckIconSolid,
    ChartBarIcon as ChartBarIconSolid,
    HandRaisedIcon as HandRaisedIconSolid,
} from '@heroicons/vue/24/solid';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const canManage = computed(() => page.props.auth.can.manageLeague);

const nav = computed(() => {
    const items = [
        { label: 'Acasă', href: '/league', match: '/league', icon: HomeIcon, iconActive: HomeIconSolid, exact: true },
        { label: 'Clasamente', href: '/league/championships', match: '/league/championships', icon: TrophyIcon, iconActive: TrophyIconSolid },
        { label: 'Cupă', href: '/league/cups', match: '/league/cups', icon: GiftIcon, iconActive: GiftIconSolid },
        { label: 'Amicale', href: '/league/friendlies', match: '/league/friendlies', icon: HandRaisedIcon, iconActive: HandRaisedIconSolid },
        { label: 'Statistici', href: '/league/stats', match: '/league/stats', icon: ChartBarIcon, iconActive: ChartBarIconSolid },
    ];

    if (canManage.value) {
        items.push(
            { label: 'Echipe', href: '/league/teams', match: '/league/teams', icon: ShieldCheckIcon, iconActive: ShieldCheckIconSolid },
        );
    }

    return items;
});

const isActive = (item) => (item.exact ? page.url === item.match : page.url === item.match || page.url.startsWith(item.match + '/'));

const logout = () => router.post(route('logout'));
</script>

<template>
    <Head :title="title" />

    <div class="min-h-[100dvh] w-full bg-pitch-950 text-slate-100 font-sans antialiased selection:bg-emerald-500/30">
        <div class="pwa-statusbar" aria-hidden="true"></div>

        <!-- ambient background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 h-[26rem] w-[26rem] rounded-full bg-emerald-500/20 blur-[100px]"></div>
            <div class="absolute top-1/3 -right-40 h-[24rem] w-[24rem] rounded-full bg-blue-600/20 blur-[100px]"></div>
            <div class="absolute bottom-0 left-1/4 h-[20rem] w-[20rem] rounded-full bg-violet-600/10 blur-[100px]"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:40px_40px]"></div>
        </div>

        <!-- App top bar: sticky, not fixed — iOS blurs a fixed header in the Home Screen app -->
        <header
            class="sticky top-0 z-40 border-b border-white/10 bg-pitch-950 px-4"
            style="padding-top: max(0.75rem, env(safe-area-inset-top)); padding-bottom: 0.75rem;"
        >
            <div class="mx-auto flex max-w-3xl items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-blue-500 text-base shadow-lg shadow-emerald-500/20">⚽</div>
                    <span class="font-display text-base font-bold tracking-tight text-white">FIFA League</span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        href="/league/settings"
                        title="Setări"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-base transition active:scale-95"
                        :style="{ backgroundColor: (user?.avatar_color || '#334155') + '33', border: `1px solid ${user?.avatar_color || '#334155'}` }"
                    >
                        {{ user?.avatar_emoji || '🎮' }}
                    </Link>
                    <button @click="logout" title="Ieși din cont" class="rounded-full p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-rose-400 active:scale-95">
                        <ArrowRightStartOnRectangleIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main
            class="relative mx-auto w-full max-w-3xl px-3 sm:px-6"
            style="padding-top: 0.75rem; padding-bottom: calc(5rem + env(safe-area-inset-bottom));"
        >
            <header v-if="$slots.header" class="mb-5">
                <slot name="header" />
            </header>
            <slot />
        </main>

        <!-- Bottom tab bar -->
        <nav
            class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-pitch-950"
            style="padding-bottom: env(safe-area-inset-bottom);"
        >
            <div class="mx-auto flex max-w-3xl items-stretch justify-around px-2">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium transition active:scale-95"
                    :class="isActive(item) ? 'text-emerald-400' : 'text-slate-500'"
                >
                    <component :is="isActive(item) ? item.iconActive : item.icon" class="h-6 w-6" />
                    {{ item.label }}
                    <span class="mt-0.5 h-1 w-1 rounded-full" :class="isActive(item) ? 'bg-emerald-400' : 'bg-transparent'"></span>
                </Link>
            </div>
        </nav>
    </div>
</template>
