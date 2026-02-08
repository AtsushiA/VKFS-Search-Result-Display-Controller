( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, InnerBlocks } = wp.blockEditor || wp.editor;
    const { PanelBody, TextControl, SelectControl } = wp.components;
    const { Fragment } = wp.element;

    registerBlockType( 'vkfs/search-result-controller', {
        title: 'VKFS Search Result Controller',
        icon: 'filter',
        category: 'widgets',
        attributes: {
            vkfs_post_type: { type: 'string' },
            category_name: { type: 'string' },
            keyword: { type: 'string' },
            category_operator: { type: 'string' },
        },

        edit: function( props ) {
            const { attributes, setAttributes, className } = props;

            return (
                wp.element.createElement( Fragment, null,
                    wp.element.createElement( InspectorControls, null,
                        wp.element.createElement( PanelBody, { title: 'Conditions', initialOpen: true },
                            wp.element.createElement( TextControl, {
                                label: 'vkfs_post_type',
                                value: attributes.vkfs_post_type || '',
                                onChange: function( val ) { setAttributes( { vkfs_post_type: val } ); }
                            } ),
                            wp.element.createElement( TextControl, {
                                label: 'category_name',
                                value: attributes.category_name || '',
                                onChange: function( val ) { setAttributes( { category_name: val } ); }
                            } ),
                            wp.element.createElement( TextControl, {
                                label: 'keyword',
                                value: attributes.keyword || '',
                                onChange: function( val ) { setAttributes( { keyword: val } ); }
                            } ),
                            wp.element.createElement( SelectControl, {
                                label: 'category_operator',
                                value: attributes.category_operator || '',
                                options: [
                                    { label: '—', value: '' },
                                    { label: 'or', value: 'or' },
                                    { label: 'and', value: 'and' },
                                ],
                                onChange: function( val ) { setAttributes( { category_operator: val } ); }
                            } ),
                            // NOTE: form selector and candidate mapping controls removed per request
                        )
                    ),

                    wp.element.createElement( 'div', { className: className },
                        wp.element.createElement( 'p', null, 'This content will be shown only when the configured VKFS query parameters match the page URL.' ),
                        wp.element.createElement( InnerBlocks, {
                            renderAppender: InnerBlocks.ButtonBlockAppender
                        } )
                    )
                )
            );
        },

        save: function( props ) {
            // Save inner blocks wrapped in a div, similar to block-visibility-controller pattern
            // Post content will be: <div>...InnerBlocks rendered HTML...</div>
            // This gets passed to PHP render_callback as $content
            return wp.element.createElement( 'div', null,
                wp.element.createElement( InnerBlocks.Content )
            );
        }
    } );

} )( window.wp );
