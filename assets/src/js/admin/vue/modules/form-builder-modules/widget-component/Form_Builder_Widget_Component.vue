<template>
    <div class="cptm-form-builder-group-field-item">
        <!-- Widget Actions -->
        <div class="cptm-form-builder-group-field-item-actions">
            <a href="#" class="cptm-form-builder-group-field-item-action-link action-trash"
                v-if="canTrashWidget" @click.prevent="$emit( 'trash-widget' )"
            >
                <span aria-hidden="true" class="uil uil-trash-alt"></span>
            </a>
        </div>

        <!-- Widget Titlebar -->
        <draggable-list-item @drag-start="$emit( 'drag-start' )" @drag-end="$emit( 'drag-end' )">
            <form-builder-widget-titlebar-component
                :label="widgetTitle"
                :sublabel="widgetSubtitle"
                :expanded="expandState"
                @toggle-expand="toggleExpand"
            />
        </draggable-list-item>
        
        <!-- Widget Body -->
        <slide-up-down :active="expandState" :duration="500">
            <div class="cptm-form-builder-group-field-item-body" v-if="widget_fields && typeof widget_fields === 'object'">
                <field-list-component 
                    :root="activeWidgets"
                    :section-id="widgetKey"
                    :field-list="widget_fields" 
                    :value="( activeWidgets[ widgetKey ] ) ? activeWidgets[ widgetKey ] : ''"
                    @update="$emit( 'update-widget-field', { widget_key: widgetKey, payload: $event} )"
                />
            </div>
        </slide-up-down>
    </div>
</template>

<script>
export default {
    name: 'form-builder-widget-component',
    props: {
        widgetKey: {
            default: '',
        },
        activeWidgets: {
            default: '',
        },
        avilableWidgets: {
            default: '',
        },
        groupData: {
            default: '',
        },
        isEnabledGroupDragging: {
            default: false,
        },
        untrashableWidgets: {
            default: '',
        },
    },

    created() {
        this.sync();
    },

    watch: {
        widgetKey() {
            if ( this.activeWidgetsIsUpdating ) { return; }
            this.sync();
        },

        activeWidgets() {
            this.activeWidgetsIsUpdating = true;
            this.sync();
            this.activeWidgetsIsUpdating = false;
        },

        groupDataFields() {
            if ( this.activeWidgetsIsUpdating ) { return; }
            this.sync();
        },

        avilableWidgets() {
            if ( this.activeWidgetsIsUpdating ) { return; }
            this.sync();
        },
    },

    computed: {
        groupDataFields() {
            return this.groupData.fields;
        },

        widgetTitle() {
            let label = '';

            if ( this.activeWidgets[ this.widgetKey ] && this.activeWidgets[ this.widgetKey ].label ) {
                label = this.activeWidgets[ this.widgetKey ].label;
            }

            if ( ! label.length && (this.current_widget && this.current_widget.label) ) {
                label = this.current_widget.label;
            }

            return label;  
        },

        widgetSubtitle() {
            let label = '';

            if (  ! ( this.activeWidgets[ this.widgetKey ] && this.activeWidgets[ this.widgetKey ].label ) ) {
                return '';
            }

            if ( this.current_widget && this.current_widget.label ) {
                label = this.current_widget.label;
            }

            return label;
        },

        expandState() {
            let state = this.expanded;

            if ( this.isEnabledGroupDragging ) {
                state = false;
            }

            return state;
        },

        canTrashWidget() {
            if ( typeof this.current_widget.canTrash === 'undefined' ) {
                return true;
            }

            return this.current_widget.canTrash;
        }
    },

    data() {
        return {
            current_widget: '',
            widget_fields: '',
            expanded: false,
            widgetIsDragging: false,

            activeWidgetsIsUpdating: false,
        }
    },

    methods: {
        sync() {
            this.syncCurrentWidget();
            this.syncWidgetFields();
        },

        syncCurrentWidget() {
            if ( ! this.avilableWidgets ) { return ''; }
            if ( typeof this.avilableWidgets !== 'object' ) { return ''; }

            if ( ! this.activeWidgets ) { return '' }
            if ( ! this.activeWidgets[ this.widgetKey ] ) { return ''; }

            const current_widget = this.activeWidgets[ this.widgetKey ];
            const widget_group = ( current_widget.widget_group ) ? current_widget.widget_group : '';
            const widget_name = ( current_widget.widget_name ) ? current_widget.widget_name : '';

            if ( ! this.avilableWidgets[ widget_group ] ) { return ''; }

            let the_current_widget = null;
            let current_widget_name = '';

            if ( this.avilableWidgets[ widget_group ][ widget_name ] ) {
                the_current_widget = this.avilableWidgets[ widget_group ][ widget_name ];
                current_widget_name = widget_name;
            }

            if ( this.avilableWidgets[ widget_group ][ this.widgetKey ] ) {
                the_current_widget = this.avilableWidgets[ widget_group ][ this.widgetKey ];
                current_widget_name = this.widgetKey;
            }

            if ( ! the_current_widget ) { return ''; }
            this.checkIfHasUntrashableWidget( widget_group, current_widget_name );

            this.current_widget = the_current_widget;
        },

        syncWidgetFields() {
            if ( ! this.current_widget ) { return ''; }
            if ( typeof this.current_widget !== 'object' ) { return ''; }
            if ( ! this.current_widget.options ) { return ''; }

            this.widget_fields = this.current_widget.options;
        },

        toggleExpand() {
            this.expanded = ! this.expanded;
        },

        checkIfHasUntrashableWidget( widget_group, widget_name ) {
            if ( ! this.untrashableWidgets ) { return; }
            if ( typeof this.untrashableWidgets !== 'object' ) { return; }

            for ( let widget in this.untrashableWidgets ) {
                if ( this.untrashableWidgets[ widget ].widget_group !== widget_group ) { continue; }
                if ( this.untrashableWidgets[ widget ].widget_name !== widget_name ) { continue; }

                this.$emit( 'found-untrashable-widget' );
                return;
            }

        }
    }
}
</script>