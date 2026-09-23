<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<!--
    Prospect's own mark, the same funnel as the favicon, rather than the Laravel
    logo every app cloned from the template was still showing. The browser tab and
    the sidebar were disagreeing about what this app is.

    Same viewBox and path as public/favicon.svg, without its background tile:
    AppLogo already draws one in --sidebar-primary. Filled, because every caller
    passes `fill-current`.
-->
<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 512 512"
        :class="className"
        v-bind="$attrs"
    >
        <path fill="currentColor" d="M104 128h304L290 272v132h-68V272z" />
    </svg>
</template>
