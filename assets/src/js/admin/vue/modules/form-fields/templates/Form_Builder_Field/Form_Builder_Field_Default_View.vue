<template>
  <div class="cptm-form-builder cptm-row">
    <div class="cptm-col-6">
      <div class="cptm-form-builder-active-fields">
        <h3 class="cptm-title-3">Active Fields</h3>
        <p class="cptm-description-text">
          Click on a field to edit, Drag & Drop to reorder
        </p>

        <button
          type="button"
          class="cptm-btn"
          v-if="showGroupDragToggleButton"
          :class="forceExpandStateTo ? 'cptm-btn-primary' : ''"
          @click="toggleEnableWidgetGroupDragging"
        >
          {{ forceExpandStateTo ? "Disable Section Dragging" : "Enable Section Dragging" }}
        </button>
        
        <form-builder-fields-group
          v-model="active_widget_groups"
          :general-settings="generalSettings"
          :group-settings="groupSettings"
          :activeWidgetFields="active_widget_fields"
          :avilable-widgets="avilable_widgets"
          :group-fields="groupFields"
          :is-enabled-group-dragging="isEnabledGroupDragging"
          :incoming-dragging-widget="currentDraggingWidget"
          :incoming-dragging-group="currentDraggingGroup"
          @update-widget-field="updateWidgetField"
          @insert-widget="insertWidget"
          @trash-widget="trashWidget"
          @group-drag-end="handleGroupDragEnd"
          @widget-drag-end="handleWidgetDragEnd"
        />
      </div>
    </div>

    <div class="cptm-col-6 cptm-col-sticky">
      <template v-for="(widget_group, widget_group_key) in widgets">
        <form-builder-widget-list-section-component
          :key="widget_group_key"
          :field-id="fieldId"
          v-bind="widget_group"
          :widget-group="widget_group_key"
          :selected-widgets="active_widget_fields"
          :active-widget-groups="active_widget_groups"
          @update-widget-list="updateWidgetList"
          @drag-start="handleWidgetListItemDragStart(widget_group_key, $event)"
          @drag-end="handleWidgetListItemDragEnd(widget_group_key, $event)"
        />
      </template>
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import helpers from "../../../../mixins/helpers";

export default {
  name: "form-builder-field-default-view",
  mixins: [helpers],
  props: {
    fieldId: {
      type: [String, Number],
      required: false,
      default: "",
    },
    widgets: {
      default: false,
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

  created() {
    this.initSetup();
  },

  watch: {
    active_widget_groups() {
      this.sync()
    }
  },

  computed: {
    finalValue() {
      const updated_value = {
        fields: this.active_widget_fields,
        groups: this.active_widget_groups,
      };

      return updated_value;
    },
    
    showGroupDragToggleButton() {
      let show_button = true;

      if (!this.active_widget_groups) {
        show_button = false;
      }

      if ( this.groupSettings && typeof this.groupSettings.draggable !== "undefined" && !this.groupSettings.draggable ) {
        show_button = false;
      }

      return show_button;
    },
  },

  data() {
    return {
      local_value: {},
      isSyncing: false,

      active_widget_fields: {},
      active_widget_groups: [],
      avilable_widgets: {},

      forceExpandStateTo: "", // expand | 'collapse'
      isEnabledGroupDragging: false,

      currentDraggingGroup: null,
      currentDraggingWidget: null,
    };
  },

  methods: {
    initSetup() {
      this.setupActiveWidgetFields();
      this.setupActiveWidgetGroups();
    },

    sync() {
      if ( this.isSyncing ) {
        return;
      }

      this.isSyncing = true;
      this.$emit( "update", this.finalValue );
      this.isSyncing = false;
    },

    // setupActiveWidgetFields
    setupActiveWidgetFields() {
      if ( ! this.value ) { return; }
      if ( typeof this.value !== "object" ) { return; }

      if ( ! this.value.fields) { return; }
      if ( typeof this.value.fields !== "object") { return; }

      let active_widget_fields = Array.isArray( this.value.fields ) ? {} : this.value.fields;
      active_widget_fields = this.sanitizeActiveWidgetFields( active_widget_fields );

      this.active_widget_fields = active_widget_fields;

      this.sync();
      this.$emit("updated-state");
      this.$emit("active-widgets-updated");
    },

    // sanitizeActiveWidgetFields
    sanitizeActiveWidgetFields( active_widget_fields ) {
      if ( ! active_widget_fields ) { return {} };
      if ( typeof active_widget_fields !== 'object' ) { return {} };

      if ( typeof active_widget_fields.field_key !== 'undefined' ) {
        delete active_widget_fields.field_key;
      }

      for ( let widget_key in active_widget_fields ) {

        if ( typeof active_widget_fields[ widget_key ] !== 'object' ) {
          delete active_widget_fields[ widget_key ];
        }

        active_widget_fields[ widget_key ].widget_key = widget_key;
      }

      return active_widget_fields;
    },

    // setupActiveWidgetGroups
    setupActiveWidgetGroups() {
      if ( ! this.value ) return;
      if ( typeof this.value !== "object" ) return;
      if ( ! this.valueHasValidGroupData() ) return;

      this.active_widget_groups = this.value.groups;
      this.sync();
      this.$emit("active-group-updated");
    },

    valueHasValidGroupData() {
      if ( ! this.value ) return false;
      if ( typeof this.value !== "object" ) return false;
      if ( ! Array.isArray( this.value.groups ) ) return false;
        
      return true;
    },
  
    // updateWidgetList
    updateWidgetList(widget_list) {
      if ( ! widget_list) { return; }
      if (typeof widget_list !== "object") { return; }
      if (typeof widget_list.widget_group === "undefined") { return; }
      if (typeof widget_list.base_widget_list === "undefined") { return; }

      Vue.set( this.avilable_widgets, widget_list.widget_group, widget_list.base_widget_list );
    },

    updateWidgetField(payload) {
      Vue.set(
        this.active_widget_fields[payload.widget_key],
        payload.payload.key,
        payload.payload.value
      );

      this.sync();
      this.$emit("updated-state");
      this.$emit("widget-field-updated");
    },

    handleWidgetDragEnd() {
      this.currentDraggingWidget = null;
    },

    handleWidgetListItemDragStart(widget_group_key, payload) {
      if ( payload.widget && typeof payload.widget.type !== "undefined" && "section" === payload.widget.type ) {
        this.currentDraggingGroup = { 
          from: "available_widgets", widget_group_key, widget_key: payload.widget_key, widget: payload.widget 
        };

        this.forceExpandStateTo = "collapse";
        this.isEnabledGroupDragging = true;

        return;
      }

      this.currentDraggingWidget = {
        from: "available_widgets", widget_group_key, widget_key: payload.widget_key, widget: payload.widget,
      };
    },

    handleWidgetListItemDragEnd() {
      this.currentDraggingWidget = null;
      this.currentDraggingGroup = null;
    },

    insertWidget( payload ) {
      if ( Array.isArray( this.active_widget_fields ) ) {
          this.active_widget_fields = {};
      }

      this.sync();
      Vue.set( this.active_widget_fields, payload.widget_key, payload.options );
    },

    trashWidget(payload) {
      Vue.delete(this.active_widget_fields, payload.widget_key);

      this.sync();
      this.$emit("updated-state");
      this.$emit("widget-field-trashed");
      this.$emit("active-widgets-updated");
    },

    handleGroupDragEnd() {
      this.currentDraggingGroup = null;
    },

    // Other Tasks
    toggleEnableWidgetGroupDragging() {
      this.forceExpandStateTo = !this.forceExpandStateTo ? "collapse" : ""; // expand | 'collapse'
      this.isEnabledGroupDragging = !this.isEnabledGroupDragging;
    },
  },
};
</script>
