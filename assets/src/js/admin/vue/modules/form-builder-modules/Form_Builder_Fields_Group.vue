<template>
    <div class="cptm-form-builder-active-fields-container" v-if="active_widget_groups">
        <draggable-list-item-wrapper
            list-id="widget-group"
            v-for="(widget_group, widget_group_key) in active_widget_groups"
            :key="widget_group_key"
            :is-dragging-self="current_dragging_group && widget_group_key === current_dragging_group.widget_group_key"
            :droppable="current_dragging_group"
            @drop="handleGroupDrop(widget_group_key, $event)"
        >
            <form-builder-widget-group-component
                :group-key="widget_group_key"
                :field-id="fieldId"
                :active-widgets="activeWidgetFields"
                :avilable-widgets="avilableWidgets"
                :group-data="widget_group"
                :group-settings="groupSettingsProp"
                :group-fields="groupFields"
                :widget-is-dragging="widgetIsDragging"
                :current-dragging-group="current_dragging_group"
                :current-dragging-widget="current_dragging_widget"
                :is-enabled-group-dragging="isEnabledGroupDragging"
                @update-group-field="updateGroupField(widget_group_key, $event)"
                @update-widget-field="updateWidgetField"
                @trash-widget="trashWidget(widget_group_key, $event)"
                @trash-group="trashGroup(widget_group_key)"
                @widget-drag-start="handleWidgetDragStart(widget_group_key, $event)"
                @widget-drag-end="handleWidgetDragEnd()"
                @drop-widget="handleWidgetDrop(widget_group_key, $event)"
                @group-drag-start="handleGroupDragStart(widget_group_key)"
                @group-drag-end="handleGroupDragEnd()"
                @append-widget="handleAppendWidget(widget_group_key)"
            />
        </draggable-list-item-wrapper>

        <div class="cptm-form-builder-active-fields-footer" v-if="showAddNewGroupButton">
            <button 
                type="button" 
                class="cptm-btn cptm-btn-secondery" 
                @click="addNewGroup" 
                v-html="addNewGroupButtonLabel"
            >
            </button>
        </div>
    </div>
</template>

<script>
import Vue from "vue";
export default {
    name: 'form-builder-fields-group',
    props: {
        fieldId: {
            required: false,
        },
        value: {
            required: false,
        },
        activeWidgetFields: {
            required: false,
        },
        avilableWidgets: {
            required: false,
        },
        generalSettings: {
            required: false,
        },
        groupSettings: {
            required: false,
        },
        groupFields: {
            required: false,
        },
        isEnabledGroupDragging: {
            required: false,
        },
        incomingDraggingGroup: {
            required: false,
        },
        incomingDraggingWidget: {
            required: false,
        },
    },

    created() {
        this.setupActiveWidgetGroups();
    },

    computed: {
        widgetIsDragging() {
            return this.current_dragging_widget ? true : false;
        },

        groupSettingsProp() {
            if ( typeof this.groupSettings !== 'object' ) {
                return this.groupSettings;
            }

            if ( typeof this.groupSettings.minGroup === "undefined" ) {
                return this.groupSettings;
            }

            if (this.active_widget_groups.length <= this.groupSettings.minGroup) {
                this.groupSettings.canTrash = false;
            }

            return this.groupSettings;
        },

        showAddNewGroupButton() {
            let show_button = true;

            if ( this.generalSettings && typeof this.generalSettings.allowAddNewGroup !== "undefined" && !this.generalSettings.allowAddNewGroup ) {
                show_button = false;
            }

            return show_button;
        },

        addNewGroupButtonLabel() {
            let button_label = "Add New";

            if (this.generalSettings && this.generalSettings.addNewGroupButtonLabel) {
                button_label = this.generalSettings.addNewGroupButtonLabel;
            }

            return button_label;
        },
    },

    watch: {
        incomingDraggingGroup() {
            if ( ! this.incomingDraggingGroup ) return;

            this.current_dragging_group = this.incomingDraggingGroup;
        },

        incomingDraggingWidget() {
            if ( ! this.incomingDraggingWidget ) return;

            this.current_dragging_widget = this.incomingDraggingWidget;
        },
    },

    data() {
        return {
            active_widget_groups: [],
            current_dragging_group: null,
            current_dragging_widget: null,

            default_group: [
                {
                    type: "general_group",
                    label: this.groupSettings && this.groupSettings.defaultGroupLabel ? this.groupSettings.defaultGroupLabel : "Section",
                    fields: [],
                },
            ],
        }
    },

    methods: {
        // setupActiveWidgetGroups
        setupActiveWidgetGroups() {
            // console.log( this.value );
            // console.log( this.avilableWidgets );

            if ( ! this.value ) return;
            if ( ! Array.isArray( this.value ) ) return;

            this.active_widget_groups = this.sanitizeActiveWidgetGroups( this.value );

            this.$emit("input", this.active_widget_groups);
            this.$emit("active-group-updated");
        },

        // sanitizeActiveWidgetGroups
        sanitizeActiveWidgetGroups( _active_widget_groups ) {
            let active_widget_groups = _active_widget_groups;
            if ( ! Array.isArray( active_widget_groups ) ) { 
                active_widget_groups = [];
            }

            let group_index = 0;
            for ( let widget_group of active_widget_groups ) {
                if ( typeof widget_group.label === 'undefined' ) {
                    active_widget_groups[ group_index ].label = '';
                }

                if ( typeof widget_group.fields === 'undefined' || ! Array.isArray( widget_group.fields ) ) {
                    active_widget_groups[ group_index ].fields = [];
                }

                let field_index = 0;
                for ( let field of widget_group.fields ) {
                    if ( typeof this.activeWidgetFields[ field ] === 'undefined' ) {
                        delete active_widget_groups[ group_index ].fields[ field_index ];
                    }

                    field_index++;
                }

                group_index++;
            }

            return active_widget_groups;
        },

        updateGroupField(widget_group_key, payload) {
            Vue.set(
                this.active_widget_groups[widget_group_key],
                payload.key,
                payload.value
            );

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("group-field-updated");
        },

        updateWidgetField(payload) {
            this.$emit("update-widget-field", payload );
            this.$emit("updated");
            this.$emit("widget-field-updated");
        },

        trashWidget(widget_group_key, payload) {
            let index = this.active_widget_groups[widget_group_key].fields.indexOf(
                payload.widget_key
            );

            this.active_widget_groups[widget_group_key].fields.splice(index, 1);

            this.$emit("trash-widget", payload);
            this.$emit("updated");
            this.$emit("widget-field-trashed");
            this.$emit("active-widgets-updated");
        },

        trashGroup(widget_group_key) {
            let group_fields = this.active_widget_groups[widget_group_key].fields;

            if (group_fields.length) {
                for ( let widget_key of group_fields ) {
                    this.$emit("trash-widget", { widget_key: widget_key });
                }
            }

            Vue.delete(this.active_widget_groups, widget_group_key);

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("group-updated");
            this.$emit("group-trashed");
            this.$emit("active-widgets-updated");
        },

        handleWidgetDragStart(widget_group_key, payload) {
            this.current_dragging_widget = {
                from: "active_widgets",
                widget_group_key,
                widget_index: payload.widget_index,
                widget_key: payload.widget_key,
            };
        },

        handleWidgetDragEnd() {
            this.current_dragging_widget = null;
            this.$emit("widget-drag-end");
        },

        handleGroupDragStart(widget_group_key) {
            this.current_dragging_group = {
                from: "active_widgets",
                widget_group_key,
            };
        },

        handleGroupDragEnd() {
            this.current_dragging_group = null;
            this.$emit("group-drag-end");
        },

        handleGroupDrop(widget_group_key, payload) {

            let dropped_in = {
                widget_group_key,
                drop_direction: payload.drop_direction,
            };

            if ("active_widgets" === this.current_dragging_group.from) {
                this.handleGroupReorderFromActiveWidgets( this.current_dragging_group, dropped_in );
            }

            if ("available_widgets" === this.current_dragging_group.from) {
                this.handleGroupInsertFromAvailableWidgets( this.current_dragging_group, dropped_in );
            }

            this.current_dragging_group = null;
            this.$emit("group-drag-end");
        },

        handleGroupReorderFromActiveWidgets(from, to) {
            let origin_data = this.active_widget_groups[from.widget_group_key];

            let dest_index = from.widget_group_key < to.widget_group_key ? to.widget_group_key - 1 : to.widget_group_key;
            dest_index = "after" === to.drop_direction ? dest_index + 1 : dest_index;

            this.active_widget_groups.splice(from.widget_group_key, 1);
            this.active_widget_groups.splice(dest_index, 0, origin_data);

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("group-reordered");
        },

        handleGroupInsertFromAvailableWidgets(from, to) {
            let group = JSON.parse(JSON.stringify(this.default_group[0]));

            let widget = from.widget;
            let option_data = this.getOptionDataFromWidget(widget);
            delete widget.options;

            Object.assign(group, widget);
            Object.assign(group, option_data);

            let dest_index = ("before" === to.drop_direction) ? to.widget_group_key - 1 : to.widget_group_key;
            dest_index = "after" === to.drop_direction ? to.widget_group_key + 1 : to.widget_group_key;
            dest_index = dest_index < 0 ? 0 : dest_index;
            dest_index = dest_index >= this.active_widget_groups.length ? this.active_widget_groups.length : dest_index;

            this.active_widget_groups.splice(dest_index, 0, group);

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("widget-inserted");
        },

        getOptionDataFromWidget(widget) {
            let field_data_options = {};
            if (widget.options && typeof widget.options === "object") {
                for (let option_key in widget.options) {
                    field_data_options[option_key] = typeof widget.options[option_key].value !== "undefined" ? widget.options[option_key].value : "";
                }
            }

            return field_data_options;
        },

        handleAppendWidget(widget_group_key) {
            if (!this.current_dragging_widget) {
                return;
            }

            let payload = { widget_index: this.active_widget_groups[widget_group_key].fields.length - 1 };

            this.handleWidgetDrop(widget_group_key, payload);
        },

        handleWidgetDrop(widget_group_key, payload) {
            let dropped_in = {
                widget_group_key,
                widget_key: payload.widget_key,
                widget_index: payload.widget_index,
                drop_direction: payload.drop_direction,
            };

            // handleWidgetReorderFromActiveWidgets
            if ("active_widgets" === this.current_dragging_widget.from) {
                this.handleWidgetReorderFromActiveWidgets( this.current_dragging_widget, dropped_in );
                this.current_dragging_widget = null;

                this.$emit("widget-drag-end");

                return;
            }

            // handleWidgetInsertFromAvailableWidgets
            if ("available_widgets" === this.current_dragging_widget.from) {
                this.handleWidgetInsertFromAvailableWidgets( this.current_dragging_widget, dropped_in );
                this.current_dragging_widget = null;

                this.$emit("widget-drag-end");
            }
        },

        handleWidgetReorderFromActiveWidgets(from, to) {
            let from_fields = this.active_widget_groups[from.widget_group_key].fields;
            let to_fields = this.active_widget_groups[to.widget_group_key].fields;

            // If Reordering in same group
            if (from.widget_group_key === to.widget_group_key) {
                let origin_data = from_fields[from.widget_index];
                let dest_index = from.widget_index < to.widget_index ? to.widget_index - 1 : to.widget_index;

                dest_index = "after" === to.drop_direction ? dest_index + 1 : dest_index;

                this.active_widget_groups[from.widget_group_key].fields.splice( from.widget_index, 1 );
                this.active_widget_groups[to.widget_group_key].fields.splice( dest_index, 0, origin_data );

                this.$emit("input", this.active_widget_groups);
                return;
            }

            // If Reordering to diffrent group
            let origin_data = from_fields[from.widget_index];
            let dest_index = "before" === to.drop_direction ? to.widget_index - 1 : to.widget_index;
            dest_index = "after" === to.drop_direction ? to.widget_index + 1 : to.widget_index;
            dest_index = dest_index < 0 ? 0 : dest_index;
            dest_index = dest_index >= to_fields.length ? to_fields.length : dest_index;

            this.active_widget_groups[from.widget_group_key].fields.splice( from.widget_index, 1 );
            this.active_widget_groups[to.widget_group_key].fields.splice( dest_index, 0, origin_data );

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("active-widgets-updated");
        },

        handleWidgetInsertFromAvailableWidgets(from, to) {
            let field_data_options = this.getOptionDataFromWidget(from.widget);
            let inserting_widget_key = this.genarateWidgetKeyForActiveWidgets( from.widget_key );

            if ( field_data_options.field_key ) {
                let unique_field_key = this.genarateFieldKeyForActiveWidgets( field_data_options );
                field_data_options.field_key = unique_field_key;
            }
            
            field_data_options.widget_key = inserting_widget_key;

            this.$emit("insert-widget", { 
                widget_key: inserting_widget_key, 
                options: field_data_options 
            });

            let to_fields = this.active_widget_groups[to.widget_group_key].fields;
            let dest_index = "before" === to.drop_direction ? to.widget_index - 1 : to.widget_index;
            
            dest_index = "after" === to.drop_direction ? to.widget_index + 1 : to.widget_index;
            dest_index = dest_index < 0 ? 0 : dest_index;
            dest_index = dest_index >= to_fields.length ? to_fields.length : dest_index;

            this.active_widget_groups[to.widget_group_key].fields.splice( dest_index, 0, inserting_widget_key );

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
            this.$emit("widget-inserted");
        },

        getOptionDataFromWidget(widget) {
            let field_data_options = {};
            if (widget.options && typeof widget.options === "object") {
                for (let option_key in widget.options) {
                    field_data_options[option_key] = typeof widget.options[option_key].value !== "undefined" ? widget.options[option_key].value : "";
                }
            }

            return field_data_options;
        },

        genarateWidgetKeyForActiveWidgets( widget_key ) {
            if ( typeof this.activeWidgetFields[widget_key] !== "undefined" ) {
                let matched_keys = Object.keys( this.activeWidgetFields );
                
                const getUniqueKey = function( current_key, new_key  ) {
                if ( matched_keys.includes( new_key ) ) {

                    let field_id = new_key.match( /[_](\d+)$/ );
                    field_id = ( field_id ) ? parseInt( field_id[1] ) : 1;

                    const new_field_key = current_key + '_' + ( field_id + 1 );

                    return getUniqueKey( current_key, new_field_key );
                }

                return new_key;
                };

                const new_widget_key = getUniqueKey( widget_key, widget_key );
                return new_widget_key;
            }

            return widget_key;
        },

        genarateFieldKeyForActiveWidgets( field_data_options ) {
            if ( ! field_data_options.field_key ) { return ''; }
            const current_field_key = field_data_options.field_key;

            let field_keys = [];

            for ( let key in this.activeWidgetFields ) {
                if ( ! this.activeWidgetFields[ key ].field_key ) {
                continue;
                }

                field_keys.push( this.activeWidgetFields[ key ].field_key );
            }

            const getUniqueKey = function( field_key ) {
                if ( field_keys.includes( field_key ) ) {

                let field_id = field_key.match( /[-](\d+)$/ );
                field_id = ( field_id ) ? parseInt( field_id[1] ) : 1;

                const new_field_key = current_field_key + '-' + ( field_id + 1 );

                return getUniqueKey( new_field_key );
                }

                return field_key;
            };

            const unique_field_key = getUniqueKey( current_field_key );

            return unique_field_key;
        },

        addNewGroup() {
            let group = JSON.parse(JSON.stringify(this.default_group[0]));

            if (this.groupSettings) {
                Object.assign(group, this.groupSettings);
            }

            let dest_index = this.active_widget_groups.length;
            this.active_widget_groups.splice(dest_index, 0, group);

            this.$emit("input", this.active_widget_groups);
            this.$emit("updated");
        },
    }
}
</script>
