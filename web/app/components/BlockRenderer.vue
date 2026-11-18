<!--
    The Vue-side counterpart to Laravel's BlockRegistry::view() — a fixed,
    explicit mapping from block type to component, not a dynamic component
    registry or plugin system. An unrecognized `block.type` (there
    shouldn't be one; Laravel already dropped anything unwhitelisted in
    BlockRegistry::resolveForPublic) simply renders nothing instead of
    crashing the page.
-->
<template>
    <div class="space-y-8">
        <template v-for="(block, index) in blocks" :key="index">
            <HeroBlock v-if="block.type === 'hero'" :data="block.data as HeroBlockData" />
            <RichTextBlock v-else-if="block.type === 'rich_text'" :data="block.data as RichTextBlockData" />
            <ImageBlock v-else-if="block.type === 'image'" :data="block.data as ImageBlockData" />
            <CtaBlock v-else-if="block.type === 'cta'" :data="block.data as CtaBlockData" />
            <MenuBlock v-else-if="block.type === 'menu'" :data="block.data as MenuBlockData" />
            <GalleryBlock v-else-if="block.type === 'gallery'" :data="block.data as GalleryBlockData" />
            <ScheduleBlock v-else-if="block.type === 'schedule'" :data="block.data as ScheduleBlockData" />
            <FormBlock v-else-if="block.type === 'form'" :data="block.data as FormBlockData" :page-slug="pageSlug" />
        </template>
    </div>
</template>

<script setup lang="ts">
import type { CtaBlockData, FormBlockData, GalleryBlockData, HeroBlockData, ImageBlockData, MenuBlockData, PageBlock, RichTextBlockData, ScheduleBlockData } from '~/types/cms';
import HeroBlock from './blocks/HeroBlock.vue';
import RichTextBlock from './blocks/RichTextBlock.vue';
import ImageBlock from './blocks/ImageBlock.vue';
import CtaBlock from './blocks/CtaBlock.vue';
import MenuBlock from './blocks/MenuBlock.vue';
import GalleryBlock from './blocks/GalleryBlock.vue';
import ScheduleBlock from './blocks/ScheduleBlock.vue';
import FormBlock from './blocks/FormBlock.vue';

// `pageSlug` is only used by form blocks, to build their submit URL.
defineProps<{ blocks: PageBlock[]; pageSlug: string }>();
</script>
