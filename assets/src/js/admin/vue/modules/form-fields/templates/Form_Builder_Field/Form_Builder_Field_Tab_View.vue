<template>
  <div>
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

          <!-- cptm-tab-container -->
          <div class="cptm-tab-container">
            <button 
              class="directorist-button directorist-button-primary-accent directorist-mb-30"
              @click="addNewTab()"
            >
              <div class="directorist-addon-icon"><span class="fa fa-plus"></span></div>
              Add New Tab
            </button>

            <!-- directorist-tab-menu -->
            <div class="directorist-tab-menu directorist-space-top-30">
              <sortable-list
                classNames="directorist-tab-menu-item has-directorist-control-bar"
                v-for="(tab, tab_index) in active_widget_tabs" 
                v-model="active_widget_tabs"
                drop-direction="horizontal"
                gutter="5px"
                :classList="{ active: ( active_tab_index === tab_index ) ? true : false }"
                :key="tab_index"
                :index="tab_index"
                :current-dragging-item="currentDraggingMenuItem"
                @change-dragging-item="currentDraggingMenuItem = $event"
                @click="switchTab( tab_index )"
            >
                <span class="directorist-tab-menu-label" v-html="tab.label"></span>

                <!-- directorist-control-bar -->
                <div class="directorist-control-bar">
                  <div class="directorist-control-bar-item">
                    <button class="directorist-button directorist-button-sm directorist-button-circle directorist-button-primary-hover directorist-mx-0"
                      @click="toggleTabEditPanal( tab_index )"
                    >
                      <span class="fa fa-pen"></span>
                    </button>
                  </div>
                  <div class="directorist-control-bar-item">
                    <button class="directorist-button directorist-button-sm directorist-button-circle directorist-button-danger-hover directorist-mx-0"
                      @click="showTabTrashModal( tab_index )"
                    >
                      <span class="fa fa-times"></span>
                    </button>
                  </div>
                </div>
            </sortable-list>
            </div>

            <slide-up-down :active="( current_editing_tab_index != null  ) ? true : false" :duration="500">
              <div class="directorist-card directorist-mt-10" v-if="( current_editing_tab_index != null  ) ? true : false">
                <field-list-component
                  :field-list="tabFields"
                  :value="active_widget_tabs[ current_editing_tab_index ]"
                  @update="updateTabOption( current_editing_tab_index, $event )"
                />
              </div>
            </slide-up-down>

            <div class="directorist-tab-contents">
              <div class="directorist-tab-content-item" :class="{ active: ( active_tab_index === tab_index ) ? true : false }" v-for="( tab, tab_index ) in active_widget_tabs" :key="tab_index">
                <form-builder-fields-group
                  v-model="active_widget_tabs[tab_index].groups"
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
          </div>
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
            :active-widget-groups="allActiveWidgetGroups"
            @update-widget-list="updateWidgetList"
            @drag-start="handleWidgetListItemDragStart(widget_group_key, $event)"
            @drag-end="handleWidgetListItemDragEnd(widget_group_key, $event)"
          />
        </template>
      </div>
    </div>

    <div class="directorist-primary-modal" :class="{ active: ( current_trashing_tab_index != null ) ? true : false}">
      <div class="directorist-primary-modal-container directorist-contents-center">
        <div class="directorist-primary-modal-body directorist-text-center">
          <h3>Are you sure ?</h3>

          <div class="directorist-contents-center">
            <button type="button" class="directorist-button directorist-button-rounded directorist-button-danger"
              @click="trashTab( current_trashing_tab_index )"
            >
              Yes
            </button>

            <button type="button" class="directorist-button directorist-button-rounded directorist-button-primary-hover"
              @click="resetTabTrashIndex()"
            >
              No
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Vue from "vue";
import helpers from "../../../../mixins/helpers";

export default {
  name: "form-builder-field-tab-view",
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
    templateOptions: {
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
    active_widget_tabs() {
      this.sync();
    },
  },

  computed: {
    finalValue() {
      const updated_value = {
        tab_fields: this.active_widget_fields,
        tabs: this.active_widget_tabs,
      };

      return updated_value;
    },

    tabFields() {
      let tabFields = [];

      if ( ! this.templateOptions ) {
        return tabFields;
      }

      if ( typeof this.templateOptions !== 'object' ) {
        return tabFields;
      }

      if ( ! this.templateOptions.tabFields ) {
        return tabFields;
      }

      if ( typeof this.templateOptions.tabFields !== 'object' ) {
        return tabFields;
      }

      return this.templateOptions.tabFields;
    },

    allActiveWidgetGroups() {
      var all_groups = [];

      if ( ! this.hasValidTabData( this.finalValue ) ) {
        return all_groups;
      }
      
      for ( let tab of this.finalValue.tabs ) {
        if ( ! this.hasValidGroupData( tab ) ) {
          continue;
        }

        all_groups = all_groups.concat( tab.groups );
      }

      return all_groups;
    },
    
    showGroupDragToggleButton() {
      let show_button = true;

      if (!this.allActiveWidgetGroups) {
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

      currentDraggingMenuItem: null,

      active_tab_index: 0,

      active_widget_fields: {},
      avilable_widgets: {},

      active_widget_tabs: [],
      default_tab: [
            {
                label: this.tabSettings && this.tabSettings.defaultTabLabel ? this.tabSettings.defaultTabLabel : "New Tab",
                groups: [],
            },
        ],

      forceExpandStateTo: "", // expand | 'collapse'
      isEnabledGroupDragging: false,

      current_editing_tab_index: null,
      current_trashing_tab_index: null,
      tab_menu_states: {},

      currentDraggingGroup: null,
      currentDraggingWidget: null,
    };
  },

  methods: {
    initSetup() {
      this.setupActiveWidgetFields();
      this.setupActiveWidgetTabs();
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
 
      const hasValidFields = function( value ) {
        if ( ! value.fields) { return false; }
        if ( typeof value.fields !== "object") { return false; }

        return true;
      };

      const hasValidTabFields = function( value ) {
        if ( ! value.tab_fields) { return false; }
        if ( typeof value.tab_fields !== "object") { return false; }

        return true;
      };

      let fields = {};

      if ( hasValidFields( this.value ) && ! hasValidTabFields( this.value ) ) {
        fields = this.value.fields;
      }

      if ( hasValidTabFields( this.value ) ) {
        fields = this.value.tab_fields;
      }

      let active_widget_fields =  Array.isArray( fields ) ? {} : fields;
      active_widget_fields = this.sanitizeActiveWidgetFields( active_widget_fields );

      this.active_widget_fields = active_widget_fields;

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

    hasValidGroupData( value ) {
      if ( ! value ) { 
        return false;
      }
      if ( typeof value !== "object" ) { 
        return false;
      }
      if ( ! Array.isArray( value.groups ) ) {
        return false;
      }
        
      return true;
    },
    
    setupActiveWidgetTabs() {
      if ( ! this.value ) {
        return false;
      }
      if ( typeof this.value !== "object" ) {
        return false;
      }

      if ( this.hasValidGroupData( this.value ) && ! this.hasValidTabData( this.value, 'abc' ) ) {
        let new_tab = JSON.parse( JSON.stringify( this.default_tab ) );
        new_tab[0].groups = this.value.groups;

        this.active_widget_tabs = new_tab;
        this.sync();
        this.$emit("active-tab-updated");

        return;
      }

      if ( this.hasValidTabData( this.value ) ) {
        this.active_widget_tabs = this.value.tabs;

        this.sync();
        this.$emit("active-tab-updated");
      }
    },

    hasValidTabData( value ) {
      if ( ! value ) { 
        return false;
      }
      if ( typeof value !== "object" ) {
        return false;
      }
      if ( ! Array.isArray( value.tabs ) ) { 
        return false;
      }
        
      return true;
    },

    addNewTab() {
      const new_tab = JSON.parse( JSON.stringify( this.default_tab[0] ) );
      this.active_widget_tabs.push( new_tab );
      this.sync();
    },

    switchTab( index ) {
      this.active_tab_index = index;
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

      Vue.set( this.active_widget_fields, payload.widget_key, payload.options );

      this.sync();
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

    toggleEnableWidgetGroupDragging() {
      this.forceExpandStateTo = !this.forceExpandStateTo ? "collapse" : ""; // expand | 'collapse'
      this.isEnabledGroupDragging = !this.isEnabledGroupDragging;
    },

    updateTabOption( tab_index, payload ) {
      if ( typeof this.active_widget_tabs[ tab_index ] === 'undefined' ) {
        return;
      }

      Vue.set( this.active_widget_tabs[ tab_index ], payload.key, payload.value );

      this.sync();
    },

    toggleTabEditPanal( tab_index ) {
      if ( this.current_editing_tab_index === tab_index ) {
        this.current_editing_tab_index = null;
        return;
      }

      this.current_editing_tab_index = tab_index;
    },

    showTabTrashModal( tab_index ) {
      this.current_trashing_tab_index = tab_index;
    },

    trashTab( tab_index ) {
      if ( typeof this.active_widget_tabs[ tab_index ] === 'undefined' ) {
        this.resetTabTrashIndex();
        return;
      }

      // Delete Tab Groups
      if ( this.active_widget_tabs[ tab_index ].groups ) {
        for ( let group of this.active_widget_tabs[ tab_index ].groups ) {
          if ( ! group.fields ) {
            continue;
          }

          for ( let group_field of group.fields ) {
            this.trashWidget( { widget_key: group_field } );
          }
        }
      }

      Vue.delete( this.active_widget_tabs, tab_index );

      if ( this.active_tab_index === tab_index ) {
        this.active_tab_index = 0;
      }

      this.current_editing_tab_index = null;

      this.resetTabTrashIndex();
    },

    resetTabTrashIndex() {
      this.current_trashing_tab_index = null;
    },

    toggleTabMenuTooltip( tab_index, key ) {
      if ( ! Object.keys( this.tab_menu_states ).includes( tab_index.toString() ) ) {
        Vue.set( this.tab_menu_states, tab_index, {} );
      }

      if ( typeof this.tab_menu_states[ tab_index ][ key ] === 'undefined' ) {
        Vue.set( this.tab_menu_states[tab_index], key, '' );
      }

      // Reset
      for ( let tabIndex in this.tab_menu_states ) {
        for ( let stateKey in this.tab_menu_states[ tabIndex ] ) {
          if ( tabIndex === tab_index && stateKey === key_name ) {
            continue;
          }

          Vue.set( this.tab_menu_states[ tabIndex ], stateKey, '' );
        }
      }

      if ( 'active' === this.tab_menu_states[ tab_index ][ key ] ) {
        Vue.set( this.tab_menu_states[tab_index], key, '' );
        return;
      }

      if ( 'active' !== this.tab_menu_states[ tab_index ][ key ] ) {
        Vue.set( this.tab_menu_states[tab_index], key, 'active' );
      }
    },

    getTabMenuTooltipClass( tab_index, key ) {
      let class_list = {
        active: false
      };

      if ( ! Object.keys( this.tab_menu_states ).length ) {
        return class_list;
      }

      if ( ! Object.keys( this.tab_menu_states ).includes( tab_index.toString() ) ) {
        return class_list;
      }

      if ( typeof this.tab_menu_states[ tab_index ][ key ] === 'undefined' ) {
        return class_list;
      }

      if ( 'active' === this.tab_menu_states[ tab_index ][ key ] ) {
        class_list.active = true;
      }

      return class_list;
    },
  },
};
</script>
