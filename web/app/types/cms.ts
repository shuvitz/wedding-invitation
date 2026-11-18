/**
 * Mirrors Laravel's public API contract exactly (see
 * app/Http/Resources/Public/* and BlockRegistry::resolveForPublic on the
 * Laravel side). Nuxt does not re-validate or re-derive this shape — it
 * only renders what Laravel has already validated and resolved.
 */

export interface SiteMedia {
    id: number
    url: string
    alt_text: string | null
}

export interface SiteIdentity {
    name: string | null
    logo: SiteMedia | null
    favicon: SiteMedia | null
}

export interface SiteContact {
    email: string | null
    phone: string | null
    address: string | null
}

export interface SiteSocial {
    instagram: string | null
    facebook: string | null
    tiktok: string | null
    linkedin: string | null
}

export interface SiteSeo {
    meta_title: string | null
    meta_description: string | null
}

export interface SiteSettings {
    site_identity: SiteIdentity
    contact: SiteContact
    social: SiteSocial
    seo: SiteSeo
    updated_at: string | null
}

export interface NavigationItem {
    label: string
    url: string
    order: number
}

export interface HeroBlockData {
    title: string | null
    subtitle: string | null
    image_url: string | null
    width: number | null
    height: number | null
}

export interface RichTextBlockData {
    title: string | null
    body: string | null
}

export interface ImageBlockData {
    url: string | null
    alt_text: string | null
    width: number | null
    height: number | null
}

export interface CtaBlockData {
    title: string | null
    description: string | null
    button_label: string | null
    url: string | null
}

export interface MenuItem {
    name: string | null
    description: string | null
    price: string | null
    image_url: string | null
    width: number | null
    height: number | null
}

export interface MenuBlockData {
    title: string | null
    items: MenuItem[]
}

export interface GalleryItem {
    url: string | null
    alt_text: string | null
    width: number | null
    height: number | null
}

export interface GalleryBlockData {
    title: string | null
    items: GalleryItem[]
}

export interface ScheduleItem {
    title: string | null
    date: string | null // YYYY-MM-DD, wall-clock (no timezone)
    start_time: string | null // HH:MM
    end_time: string | null // HH:MM
    venue: string | null
    address: string | null
    maps_url: string | null
}

export interface ScheduleBlockData {
    title: string | null
    items: ScheduleItem[]
}

export interface FormField {
    name: string
    label: string
    type: 'text' | 'textarea' | 'number' | 'select'
    required: boolean
    options: string[]
}

export interface FormBlockData {
    form_key: string
    title: string | null
    submit_label: string | null
    success_message: string | null
    fields: FormField[]
}

export interface CouplePerson {
    name: string | null
    parents: string | null
    instagram: string | null // bare username, no @
    image_url: string | null
    width: number | null
    height: number | null
}

export interface CoupleBlockData {
    title: string | null
    items: CouplePerson[]
}

export interface GiftAccount {
    bank: string | null
    account_number: string | null // digits, may contain spaces/dashes
    account_name: string | null
}

export interface GiftBlockData {
    title: string | null
    description: string | null
    items: GiftAccount[]
}

export interface CoverBlockData {
    title: string | null
    names: string | null
    greeting: string | null
    button_label: string | null
    image_url: string | null
    width: number | null
    height: number | null
}

export type PageBlock =
    | { type: 'hero'; data: HeroBlockData }
    | { type: 'rich_text'; data: RichTextBlockData }
    | { type: 'image'; data: ImageBlockData }
    | { type: 'cta'; data: CtaBlockData }
    | { type: 'menu'; data: MenuBlockData }
    | { type: 'gallery'; data: GalleryBlockData }
    | { type: 'schedule'; data: ScheduleBlockData }
    | { type: 'form'; data: FormBlockData }
    | { type: 'couple'; data: CoupleBlockData }
    | { type: 'gift'; data: GiftBlockData }
    | { type: 'cover'; data: CoverBlockData }
    // Laravel already drops unknown block types before this reaches Nuxt
    // (BlockRegistry::resolveForPublic), but the type stays open so a
    // future block type Nuxt doesn't know about yet fails safely here too
    // instead of being a TypeScript error at the render call site.
    | { type: string; data: Record<string, unknown> }

export interface CmsPage {
    title: string
    slug: string
    is_home: boolean
    meta_title: string | null
    meta_description: string | null
    published_at: string | null
    content: { blocks: PageBlock[] }
}

export interface ApiEnvelope<T> {
    data: T
}
