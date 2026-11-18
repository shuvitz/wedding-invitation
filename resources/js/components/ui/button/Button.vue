<script setup>
import { useAttrs } from "vue"
import { Primitive } from "reka-ui"
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

const props = defineProps({
    as: { type: String, default: "button" },
    variant: { type: String, default: "default" },
    size: { type: String, default: "default" },
    asChild: { type: Boolean, default: false },
    class: { type: String, default: "" },
})

const attrs = useAttrs()
</script>

<template>
    <Primitive
        :as="props.as"
        :as-child="props.asChild"
        v-bind="attrs"
        :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class, attrs.class)"
    >
        <slot />
    </Primitive>
</template>
