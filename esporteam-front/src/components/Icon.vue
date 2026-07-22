<script setup>
import { computed } from 'vue'
import IconArrowBackUp from '@tabler/icons-vue/dist/esm/icons/IconArrowBackUp.mjs'
import IconArrowUp from '@tabler/icons-vue/dist/esm/icons/IconArrowUp.mjs'
import IconBallBasketball from '@tabler/icons-vue/dist/esm/icons/IconBallBasketball.mjs'
import IconBallFootball from '@tabler/icons-vue/dist/esm/icons/IconBallFootball.mjs'
import IconBallTennis from '@tabler/icons-vue/dist/esm/icons/IconBallTennis.mjs'
import IconBallVolleyball from '@tabler/icons-vue/dist/esm/icons/IconBallVolleyball.mjs'
import IconBarbell from '@tabler/icons-vue/dist/esm/icons/IconBarbell.mjs'
import IconBike from '@tabler/icons-vue/dist/esm/icons/IconBike.mjs'
import IconBolt from '@tabler/icons-vue/dist/esm/icons/IconBolt.mjs'
import IconBulb from '@tabler/icons-vue/dist/esm/icons/IconBulb.mjs'
import IconCalendarCheck from '@tabler/icons-vue/dist/esm/icons/IconCalendarCheck.mjs'
import IconCards from '@tabler/icons-vue/dist/esm/icons/IconCards.mjs'
import IconCheck from '@tabler/icons-vue/dist/esm/icons/IconCheck.mjs'
import IconChevronDown from '@tabler/icons-vue/dist/esm/icons/IconChevronDown.mjs'
import IconChevronRight from '@tabler/icons-vue/dist/esm/icons/IconChevronRight.mjs'
import IconClock from '@tabler/icons-vue/dist/esm/icons/IconClock.mjs'
import IconDotsVertical from '@tabler/icons-vue/dist/esm/icons/IconDotsVertical.mjs'
import IconEdit from '@tabler/icons-vue/dist/esm/icons/IconEdit.mjs'
import IconExternalLink from '@tabler/icons-vue/dist/esm/icons/IconExternalLink.mjs'
import IconFilter from '@tabler/icons-vue/dist/esm/icons/IconFilter.mjs'
import IconGripVertical from '@tabler/icons-vue/dist/esm/icons/IconGripVertical.mjs'
import IconInbox from '@tabler/icons-vue/dist/esm/icons/IconInbox.mjs'
import IconLink from '@tabler/icons-vue/dist/esm/icons/IconLink.mjs'
import IconLogout from '@tabler/icons-vue/dist/esm/icons/IconLogout.mjs'
import IconMail from '@tabler/icons-vue/dist/esm/icons/IconMail.mjs'
import IconMap from '@tabler/icons-vue/dist/esm/icons/IconMap.mjs'
import IconPlus from '@tabler/icons-vue/dist/esm/icons/IconPlus.mjs'
import IconRoute from '@tabler/icons-vue/dist/esm/icons/IconRoute.mjs'
import IconSearch from '@tabler/icons-vue/dist/esm/icons/IconSearch.mjs'
import IconSettings from '@tabler/icons-vue/dist/esm/icons/IconSettings.mjs'
import IconShare from '@tabler/icons-vue/dist/esm/icons/IconShare.mjs'
import IconShieldCheck from '@tabler/icons-vue/dist/esm/icons/IconShieldCheck.mjs'
import IconSparkles from '@tabler/icons-vue/dist/esm/icons/IconSparkles.mjs'
import IconSwimming from '@tabler/icons-vue/dist/esm/icons/IconSwimming.mjs'
import IconTrophy from '@tabler/icons-vue/dist/esm/icons/IconTrophy.mjs'
import IconUser from '@tabler/icons-vue/dist/esm/icons/IconUser.mjs'
import IconUsers from '@tabler/icons-vue/dist/esm/icons/IconUsers.mjs'
import IconWalk from '@tabler/icons-vue/dist/esm/icons/IconWalk.mjs'
import IconX from '@tabler/icons-vue/dist/esm/icons/IconX.mjs'
import IconYoga from '@tabler/icons-vue/dist/esm/icons/IconYoga.mjs'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 16 },
  stroke: { type: [Number, String], default: 1.8 },
  fill: { type: String, default: 'none' },
})

const ICONS = {
  inbox: IconInbox,
  cards: IconCards,
  map: IconMap,
  calendarCheck: IconCalendarCheck,
  user: IconUser,
  lock: IconShieldCheck,
  logout: IconLogout,
  ideas: IconBulb,
  competitors: IconUsers,
  roadmap: IconRoute,
  settings: IconSettings,
  search: IconSearch,
  plus: IconPlus,
  sparkles: IconSparkles,
  check: IconCheck,
  x: IconX,
  chevron: IconChevronRight,
  chevronDown: IconChevronDown,
  arrowUp: IconArrowUp,
  drag: IconGripVertical,
  vote: IconArrowUp,
  edit: IconEdit,
  share: IconShare,
  link: IconLink,
  filter: IconFilter,
  mail: IconMail,
  bolt: IconBolt,
  back: IconArrowBackUp,
  external: IconExternalLink,
  clock: IconClock,
  sportDefault: IconTrophy,
  sportTennis: IconBallTennis,
  sportVolleyball: IconBallVolleyball,
  sportRunning: IconWalk,
  sportSoccer: IconBallFootball,
  sportBasketball: IconBallBasketball,
  sportBike: IconBike,
  sportDumbbell: IconBarbell,
  sportSwim: IconSwimming,
  sportYoga: IconYoga,
  sportFight: IconTrophy,
}

const component = computed(() => ICONS[props.name] || IconDotsVertical)
</script>

<template>
  <component
    :is="component"
    aria-hidden="true"
    :size="size"
    :stroke="stroke"
    :fill="fill"
  />
</template>
