/* eslint-disable react/prop-types */
import React from 'react';
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow } from '@wordpress/components';

const EditSimpleShop = (props) => {
    const {
        attributes,
        setAttributes,
        className,
    } = props;

    return (
        <div className={className}>
            <RichText
                tagName="h3"
                onChange={heading => setAttributes({ heading })}
                value={attributes.heading}
                placeholder={__('Type some heading', 'simpleshop-cz')}
                keepPlaceholderOnFocus
            />
            <InspectorControls>
                <PanelBody>
                    <PanelRow>
                        <p>{__('Some content in sidebar', 'simpleshop-cz')}</p>
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
        </div>
    );
};

const SaveSimpleShop = () => null;

registerBlockType('simpleshop/test', {
    title: __('Simpleshop Test', 'simpleshop-cz'),
    icon: 'grid-view',
    category: 'simpleshop',
    edit: EditSimpleShop,
    save: SaveSimpleShop,
});
