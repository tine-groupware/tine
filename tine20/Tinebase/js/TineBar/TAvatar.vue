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
  <div class="account-user-avatar">
    <img v-if='imgSrc' class="account-user-avatar__image" :src='imgSrc'>
    <div v-else class="account-user-avatar__image_alt text-center d-flex align-items-center justify-content-center">
      <span>{{getAbbreviatedName()}}</span>
    </div>
  </div>
</template>

<script setup>
import { onBeforeMount, ref } from 'vue'

const imgSrc = ref(null)
const currentAccount = Tine.Tinebase.registry.get('currentAccount')
const getAbbreviatedName = () => {
  const t = currentAccount.accountFirstName
    ? currentAccount.accountFirstName[0] + '.' + currentAccount.accountLastName[0] + '.'
    : currentAccount.accountLastName[0]
  return t.toUpperCase()
}

onBeforeMount(async () => {
  try {
    const contact = await Tine.Addressbook.getContact({ id: currentAccount.contact_id })
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
.account-user-avatar{
  width: 30px;
  height: 30px;
  cursor: pointer;

  &__image{
    filter: invert(1);
    vertical-align: middle;
    object-fit: cover;
    width: 100%;
    height: 100%;
    border-radius: 50%;
  }

  .dark-mode &__image{
    filter: invert(1) hue-rotate(180deg);
  }

  &__image_alt{
    filter: invert(1);
    width: 100%;
    height: 100%;
    background-color: #f2f2f2;
    border-radius: 50%;
    font-size: 13px;
    font-weight: bold;
  }
}
</style>
