<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <b-button class="account-btn" variant="primary" tabindex="0" :aria-label="window.i18n._('User settings')">
  <div class="account-user-avatar">
    <img v-if='imgSrc' class="account-user-avatar__image" :src='imgSrc' :style="imageStyle">
    <div
      v-else
      class="account-user-avatar__image_alt text-center d-flex align-items-center justify-content-center"
      :class="{ 'account-user-avatar__image_alt--colored': colorSchema }"
      :style="colorSchema ? { backgroundColor: colorSchema.color, color: colorSchema.text } : null"
    >
      <span>{{getAbbreviatedName()}}</span>
    </div>
  </div>
  </b-button>
</template>

<script setup>
import { computed, onBeforeMount, ref } from 'vue'

const imgSrc = ref(null)
const colorSchema = ref(null)
// NOTE: like in addressbook, images get a border in contact color
const imageStyle = computed(() => colorSchema.value ? { border: `2px solid ${colorSchema.value.color}` } : null)
const currentAccount = Tine.Tinebase.registry.get('currentAccount')
const getAbbreviatedName = () => {
  const t = currentAccount.accountFirstName
    ? currentAccount.accountFirstName[0] + '.' + currentAccount.accountLastName[0] + '.'
    : currentAccount.accountLastName[0]
  return t.toUpperCase()
}

onBeforeMount(async () => {
  try {
    const contact = await Tine.Addressbook.getContact(currentAccount.contact_id)
    if (contact.color) {
      colorSchema.value = Tine.Tinebase.colorMgr.getSchema(contact.color)
    }
    if (contact.jpegphoto.startsWith('index.php')) {
      const t = await fetch(contact.jpegphoto).then((response) => response.blob())
      imgSrc.value = URL.createObjectURL(t)
    } else {
      imgSrc.value = null
    }
  } catch (e) {
    imgSrc.value = null
  }
})
</script>

<style scoped lang="scss">
.account-btn {
  padding-top: 0;
  padding-bottom: 0;
  &.color {
    filter: invert(0);
  }
}
.account-user-avatar{
  width: 30px;
  height: 30px;
  cursor: pointer;
  color: #000;
  &:hover {
    color: #000;
  }

  &__image{
    vertical-align: middle;
    object-fit: cover;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    box-sizing: border-box;
  }

  .dark-mode &__image{
    filter: invert(1) hue-rotate(180deg);
  }

  &__image_alt{
    width: 100%;
    height: 100%;
    background-color: #f2f2f2;
    border-radius: 50%;
    font-size: 13px;
    font-weight: bold;
  }

  // NOTE: show contact color in dark mode as is (like images)
  .dark-mode &__image_alt--colored{
    filter: invert(1) hue-rotate(180deg);
  }
}
</style>
