<template>
    <component 
        :is="tag" 
        class="directorist-sortable-list-item" 
        :class="listItemClassNames" 
        draggable="true" 
        @dragstart="handleDragStart"
        @dragend="handleDragEnd"
        @click="$emit( 'click' )"
    >
        <div class="directorist-sortable-list-item-drop-prepend-area"
            :style="dropPrependAreaStyle"
            :class="{ 'drop-horizontal': 'horizontal' === dropDirection, 'drag-enter': dragenterPrepend }"
            v-if="canReceiveItem"
            @dragover.prevent=""
            @dragenter="handleDragEnterPrepend"
            @dragleave="handleDragLeavePrepend"
            @drop="handleDropPrepend"
        >
        </div>

        <slot></slot>

        <div class="directorist-sortable-list-item-drop-append-area"
            :style="dropAppendAreaStyle"
            :class="{ 'drop-horizontal': 'horizontal' === dropDirection, 'drag-enter': dragenterAppend }"
            v-if="canReceiveItem"
            @dragover.prevent=""
            @dragenter="handleDragEnterAppend"
            @dragleave="handleDragLeaveAppend"
            @drop="handleDropAppend"
        >
        </div>
    </component>
</template>

<script>
export default {
    name: 'sortable-list',
    model: {
        prop: 'list',
        event: 'update',
    },
    props: {
        tag: {
            default: 'div',
        },
        classNames: {
            default: '',
        },
        classList: {
            default: {},
        },
        index: {
            default: 0,
        },
        currentDraggingItem: {
            required: 0,
        },
        list: {
            default: [],
        },
        dropDirection: {
            default: 'vertical', // vertical | horizontal
        },
        gutter: {
            default: '0',
        },
    },

    created() {
        // console.log({
        //     list: this.list
        // });
    },

    computed: {
        listItemClassNames() {
            const props_class_names = this.stringToClassList( this.classNames );
            const props_class_list = ( this.classList && typeof this.classList === 'object' ) ? this.classList : {};

            return {
                ...props_class_names,
                ...props_class_list,
                'is-dragging': this.currentDraggingItem === this.index,
            }
        },

        dropPrependAreaStyle() {
            let style = {};

            if ( 'vertical' === this.dropDirection ) {
                style.top = `-${this.gutter}`;
            }

            if ( 'horizontal' === this.dropDirection ) {
                style.left = `-${this.gutter}`;
            }
            
            return style;
        },

        dropAppendAreaStyle() {
            let style = {};

            if ( 'vertical' === this.dropDirection ) {
                style.bottom = `-${this.gutter}`;
            }

            if ( 'horizontal' === this.dropDirection ) {
                style.right = `-${this.gutter}`;
            }
            
            return style;
        },

        canReceiveItem() {
            if ( this.currentDraggingItem != null ) {
                return true;
            }

            return false;
        }
    },

    data() {
        return {
            isDraggingSelf: false,
            dragenterPrepend: false,
            dragenterAppend: false,
        }
    },

    methods: {
        handleDragStart() {
            const self = this;
            setTimeout( function() {
                self.isDraggingSelf = true;
                self.$emit( 'drag-start' );
                self.$emit( 'change-dragging-item', self.index );
                // console.log( 'handleDragStart', self.index );
            }, 0);
        },

        handleDragEnd() {
            this.isDraggingSelf = false;
            this.$emit( 'drag-end' );
            this.$emit( 'change-dragging-item', null );
            console.log( 'handleDragEnd', this.index );
        },

        handleDragEnterPrepend() {
            this.dragenterPrepend = true;
        },

        handleDragLeavePrepend() {
            this.dragenterPrepend = false;
        },

        handleDropPrepend() {
            const sorted_list = this.sortList({
                list: this.list, 
                from: this.currentDraggingItem, 
                to: this.index, 
                direction: 'prepend'
            });

            this.$emit( 'update', sorted_list );
        },

        handleDragEnterAppend() {
            this.dragenterAppend = true;
        },

        handleDragLeaveAppend() {
            this.dragenterAppend = false;
        },

        handleDropAppend() {
            const sorted_list = this.sortList({
                list: this.list, 
                from: this.currentDraggingItem, 
                to: this.index, 
                direction: 'append'
            });

            this.$emit( 'update', sorted_list );
        },
        
        sortList({ list, from, to, direction }) {
            let origin_list = JSON.parse( JSON.stringify( list ) );
            let origin_item = origin_list[ from ];
            let new_dest = from;

            if ( 'append' === direction && from < to ) {
                new_dest = to;
            }

            if ( 'append' === direction && from > to ) {
                new_dest = to + 1;
            }

            if ( 'prepend' === direction && from < to ) {
                new_dest = to - 1;
            }

            if ( 'prepend' === direction && from > to ) {
                new_dest = to;
            }

            if ( new_dest > origin_list.length ) {
                new_dest = origin_list.length;
            }

            if ( new_dest < 0 ) {
                new_dest = 0;
            }

            origin_list.splice( from, 1 );
            origin_list.splice( new_dest, 0, origin_item );

            console.log( { from, to, new_dest, direction } );

            return origin_list;
        },

        stringToClassList( str ) {
            if ( typeof str !== 'string' ) {
                return {};
            }

            if ( ! str.length ) {
                return {};
            }

            let class_names = str.split( ' ' );

            if ( ! class_names.length ) {
                return {};
            }
            
            var class_list = {};

            for ( let class_name of class_names ) {
                class_list[ class_name ] = true;
            }

            return class_list;
        }
    }
}
</script>