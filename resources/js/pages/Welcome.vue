<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Moon, SunMedium } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard, login } from '@/routes';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

function toggleAppearance() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}

/** The real pipeline states, in the order a company moves through them. */
const pipeline = [
    { label: 'New', count: 34, dot: 'bg-muted-foreground' },
    { label: 'Sent', count: 61, dot: 'bg-primary' },
    { label: 'Replied', count: 12, dot: 'bg-green-500' },
    { label: 'Bounced', count: 5, dot: 'bg-destructive' },
    { label: 'Closed', count: 18, dot: 'bg-muted-foreground/50' },
];

const steps = [
    {
        title: 'Keep the list worth having',
        body: 'Companies worth approaching, imported or added by hand, with the contact address and the reason this one is on the list. The ones missing an address are flagged rather than silently skipped.',
    },
    {
        title: 'Write the letter for that company',
        body: 'An open-aanbod letter and its cover email, drafted per company in Dutch or English, reviewed before anything leaves. A draft is never a sent thing until you say so.',
    },
    {
        title: 'Send it from your own address',
        body: 'Out of info@thijssensoftware.nl with the letter and CV attached, so a reply lands where you already read mail rather than in a tool you have to remember to open.',
    },
    {
        title: 'Notice what came back',
        body: 'Replies and bounces move the company along on their own. What is left is the follow-up queue: who has gone quiet long enough to be worth one more message.',
    },
];
</script>

<template>
    <!-- No title: app.ts already appends the app name to every page title, so
         setting it here renders "Prospect - Prospect". -->
    <Head />

    <div class="min-h-screen bg-background text-foreground">
        <header
            class="sticky top-0 z-20 border-b border-border bg-background/80 backdrop-blur"
        >
            <div
                class="mx-auto flex h-15 max-w-5xl items-center justify-between px-6 py-3"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg bg-primary"
                    >
                        <AppLogoIcon class="size-4 fill-current text-white" />
                    </div>
                    <span
                        class="font-mono text-[15px] font-semibold tracking-tight"
                    >
                        prospect
                    </span>
                </div>
                <nav class="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="
                            isDark ? 'Switch to light' : 'Switch to dark'
                        "
                        @click="toggleAppearance"
                    >
                        <Moon v-if="isDark" class="size-4" />
                        <SunMedium v-else class="size-4" />
                    </Button>
                    <Button
                        v-if="$page.props.auth.user"
                        :as-child="true"
                        size="sm"
                    >
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                    <Button v-else :as-child="true" size="sm">
                        <Link :href="login()">Log in</Link>
                    </Button>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6">
            <section class="py-16 sm:py-20">
                <p
                    class="font-mono text-xs tracking-[0.16em] text-primary uppercase"
                >
                    Outreach
                </p>
                <h1
                    class="mt-5 max-w-3xl text-5xl font-semibold tracking-tighter text-balance sm:text-6xl"
                >
                    Cold outreach dies in<br />
                    <span class="text-primary">a spreadsheet</span>.
                </h1>
                <p
                    class="mt-5 max-w-xl text-base text-pretty text-muted-foreground"
                >
                    Not because the letters are bad, but because nobody
                    remembers who was written to in March, which address
                    bounced, or who replied and was never answered. Prospect
                    holds the list, writes each letter for the company it is
                    going to, sends it, and keeps what came back.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Button :as-child="true" size="lg">
                        <Link :href="login()">Open Prospect</Link>
                    </Button>
                </div>
            </section>

            <section class="pb-16">
                <div
                    class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-border px-5 py-3"
                    >
                        <span class="text-sm font-medium">Pipeline</span>
                        <span class="font-mono text-xs text-muted-foreground">
                            130 companies &middot; 9 follow-ups due
                        </span>
                    </div>
                    <div class="grid gap-px bg-border sm:grid-cols-5">
                        <div
                            v-for="stage in pipeline"
                            :key="stage.label"
                            class="bg-card px-5 py-4"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full"
                                    :class="stage.dot"
                                />
                                <span
                                    class="text-xs text-muted-foreground uppercase"
                                >
                                    {{ stage.label }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-2xl font-semibold">
                                {{ stage.count }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="border-t border-border px-5 py-4 text-sm text-muted-foreground"
                    >
                        A company moves itself along: a reply makes it
                        <span class="text-foreground">replied</span>, a bounce
                        makes it <span class="text-foreground">bounced</span>.
                        Nothing has to be dragged anywhere.
                    </div>
                </div>
            </section>

            <section class="pb-16">
                <h2 class="text-2xl font-semibold tracking-tight">
                    How a pitch actually goes out
                </h2>
                <div class="mt-6 grid gap-x-10 gap-y-7 sm:grid-cols-2">
                    <div v-for="(step, index) in steps" :key="step.title">
                        <p class="font-mono text-xs text-primary">
                            {{ String(index + 1).padStart(2, '0') }}
                        </p>
                        <h3 class="mt-1 font-medium">{{ step.title }}</h3>
                        <p
                            class="mt-1.5 text-sm text-pretty text-muted-foreground"
                        >
                            {{ step.body }}
                        </p>
                    </div>
                </div>
            </section>

            <section class="pb-20">
                <div class="rounded-xl border border-border bg-card p-6">
                    <h2 class="text-lg font-semibold tracking-tight">
                        Nothing sends itself
                    </h2>
                    <p
                        class="mt-2 max-w-2xl text-sm text-pretty text-muted-foreground"
                    >
                        Every letter is written and then waits. It is read,
                        edited if it needs it, and sent deliberately, from your
                        own address, to one company at a time. There is no bulk
                        send here and there is not going to be: the whole point
                        of a letter written for one company is that it was
                        written for that company.
                    </p>
                </div>
            </section>
        </main>

        <footer class="border-t border-border">
            <div
                class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-6 py-6 text-sm text-muted-foreground"
            >
                <span>
                    One of the connected Thijssen Software workflow apps.
                </span>
                <a
                    class="hover:text-foreground"
                    href="https://github.com/Ezomic/prospect"
                >
                    Source
                </a>
            </div>
        </footer>
    </div>
</template>
