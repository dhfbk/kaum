<template>
  <div class="locale-changer">
    <select v-model="$i18n.locale">
      <option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">
        {{ lang }}
      </option>
    </select>
  </div>
  <p>{{ $t('message') }}</p>
  <p>{{ count }}</p>
  <button @click="ciao()">Incrementa</button>
</template>

<script>
import { defineComponent } from 'vue'
// import { useI18n } from 'vue-i18n'
import i18n from '../i18n.js';
import { mapState } from 'vuex'

export default defineComponent({
  name: 'HelloI18n',
  data () {
    return {
      langs: i18n.global.availableLocales
    }
  },
  computed: {
    ...mapState([
      'count'
    ])
  },
  methods: {
    ciao: function() {
      this.$store.commit('increment');
    }
  },
  mounted() {
    console.log(i18n.global.availableLocales);
  },
  setup() {
    // console.log(this.$t);
  },
})
</script>
