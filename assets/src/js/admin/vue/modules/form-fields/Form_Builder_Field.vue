<template>
  <component :is="templateName" v-bind="$props" @update="syncData"/>
</template>

<script>

export default {
  name: "form-builder-field",
  props: {
    fieldId: {
      type: [String, Number],
      required: false,
      default: "",
    },
    widgets: {
      default: false,
    },
    template: {
      default: false,
    },
    templateOptions: {
      required: false,
    },
    generalSettings: {
      default: false,
    },
    groupSettings: {
      default: false,
    },
    groupFields: {
      default: false,
    },
    value: {
      default: "",
    },
  },

  computed: {
    templateName() {
      const default_template = 'form-builder-field-default-view';

      if ( ! this.template ) {
        return default_template;
      }

      if ( typeof this.template !== 'string' ) {
        return default_template;
      }

      return `form-builder-field-${this.template}`;
    },
  },

  methods: { 
    syncData( value ) {

      if ( ! this.isValidObject( value ) ) {
        return;
      }

      const old_value = JSON.parse( JSON.stringify( this.value ) );
      const new_value = JSON.parse( JSON.stringify( value ) );

      // If has no valid old value
      if ( ! this.isValidObject( old_value ) ) {
        old_value = {};
      }

      const final_value = { ...old_value, ...new_value };

      this.$emit('update', final_value);
    },

    isValidObject( data ) {
      if ( data && typeof data === 'object' ) {
        return true;
      }

      return false;
    }
  }
};
</script>
