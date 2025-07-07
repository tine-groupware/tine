<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div class="page-wrap">
    <div>
      <b-navbar class="navbar-hr">
          <b-navbar-nav>
            <b-navbar-brand>Pastorale Dienststelle</b-navbar-brand>
            <b-nav-item :to="{ path: '/event' }">Events</b-nav-item>
            <b-nav-item-dropdown text="Topics">
              <b-dropdown-item href="#">Gottesdienst</b-dropdown-item>
              <b-dropdown-item href="#">Ehrenamtliche Tätigkeiten</b-dropdown-item>
              <b-dropdown-item href="#">Kinder</b-dropdown-item>
              <b-dropdown-item href="#">Choir</b-dropdown-item>
            </b-nav-item-dropdown>
            <b-nav-item :to="{ path: '/contact'}">Contact</b-nav-item>
          </b-navbar-nav>

          <b-navbar-nav class="ml-auto">
            <b-nav-form>
              <b-form-input v-model="input" size="sm" class="mr-sm-2" placeholder="Search"></b-form-input>
              <b-button size="sm" class="my-2 my-sm-0" type="submit">Search</b-button>
            </b-nav-form>
          </b-navbar-nav>
      </b-navbar>
    </div>
    <main>
    <div>
      <RouterView />
    </div>
    </main>
    <footer class="mx-5">
      <b-row>
        <b-col cols="8">
          <a href="http://localhost:4000/EventManager/view/#/event">{{formatMessage('Imprint')}}</a>
          &#183
          <a href="http://localhost:4000/EventManager/view/#/event">{{formatMessage('Privacy Notice')}}</a>
        </b-col>
        <b-col cols="4" class="text-right">
          <a href="http://localhost:4000/EventManager/view/#/event">{{formatMessage('to the top')}}</a>
<!--          <router-link @click.native="$scrollToTop">-->
        </b-col>
      </b-row>
    </footer>
  </div>
</template>

<script setup>
import {
  onBeforeMount,
  computed,
  inject,
  ref
} from 'vue';

import {useRoute, useRouter} from 'vue-router';
import {translationHelper} from "./keys";
const formatMessage = inject(translationHelper);

const router = useRouter();
const route = useRoute();

const search = computed({
  get() {
    return route.query.search ?? ''
  },
  set(search) {
    router.replace({ query: { search } })
  }
})

const input =ref("");

// websocket still pending, this only hide the loading mask...
onBeforeMount(async () => {
  document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
  document.getElementsByClassName('tine-viewport-poweredby')[0].style.display = 'none';
})
</script>

<style lang="scss">
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

.navbar-hr {
  border-bottom: 1px solid #333333;
}

main {
  flex-grow: 1;
}

.page-wrap{
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

</style>

