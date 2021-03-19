import assign from "lodash.assign";
import React, {useState} from 'react';
import {registerBlockType} from '@wordpress/blocks';
import {__} from '@wordpress/i18n';
import {InspectorControls} from '@wordpress/block-editor';
import {Button, DateTimePicker, PanelBody, SelectControl, TextControl, ToggleControl} from '@wordpress/components';
import v1 from './v1';

const {__experimentalGetSettings} = wp.date;
const {addFilter} = wp.hooks;
const {createHigherOrderComponent} = wp.compose;

/**
 * Add custom attributes to the blocks
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addSimpleShopAttributes = (settings, name) => {
    settings.attributes = assign(settings.attributes, {
        simpleShopGroup: {
            type: 'string'
        },
        simpleShopIsMember: {
            type: 'string'
        },
        simpleShopIsLoggedIn: {
            type: 'string'
        },
        simpleShopIgnoreDates: {
            type: 'bool'
        },
        simpleShopDaysToView: {
            type: 'string'
        },
        simpleShopSpecificDateFrom: {
            type: 'string',
            default: ''
        },
        simpleShopSpecificDateTo: {
            type: 'string'
        },

    });

    return settings;
};
addFilter('blocks.registerBlockType', 'simpleshop/attributes/custom', addSimpleShopAttributes);

const simpleShopYesNoSelect = [
    {
        label: __('Choose', 'simpleshop-cz'),
        value: ''
    },
    {
        label: __('Yes', 'simpleshop-cz'),
        value: 'yes'
    },
    {
        label: __('No', 'simpleshop-cz'),
        value: 'no'
    }
];

const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const {
            simpleShopGroup,
            simpleShopIsMember,
            simpleShopIsLoggedIn,
            simpleShopIgnoreDates,
            simpleShopDaysToView,
            simpleShopSpecificDateFrom,
            simpleShopSpecificDateTo
        } = props.attributes;

        let settings = __experimentalGetSettings();

        // To know if the current timezone is a 12 hour time with look for an "a" in the time format.
        // We also make sure this a is not escaped by a "/".
        const is12HourTime = /a(?!\\)/i.test(
            settings.formats.time
                .toLowerCase() // Test only the lower case a
                .replace(/\\\\/g, '') // Replace "//" with empty strings
                .split('').reverse().join('') // Reverse the string and test for "a" not followed by a slash
        );


        let simpleShopGroupsData = ssGutenbergVariables.groups;
        let simpleShopGroups = [{label: __('Doesn\'t matter', 'simpleshop-cz'), value: ''}];
        for (let item in simpleShopGroupsData) {
            simpleShopGroups.push({label: simpleShopGroupsData[item], value: item});
        }

        return (
            <>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody
                        title={__('SimpleShop Settings', 'simpleshop-cz')}
                        initialOpen={true}
                    >
                        <p><a href="https://podpora.redbit.cz/stitek/wp-plugin/">
                            {__('Help - SimpleShop plugin', 'simpleshop-cz')}
                        </a>
                        </p>
                        <SelectControl
                            label={__('Group', 'simpleshop-cz')}
                            value={simpleShopGroup}
                            options={simpleShopGroups}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopGroup: selectedSpacingOption
                                });
                            }}
                        />
                        <SelectControl
                            label={__('Is member', 'simpleshop-cz')}
                            value={simpleShopIsMember}
                            options={simpleShopYesNoSelect}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopIsMember: selectedSpacingOption
                                });
                            }}
                        />
                        <SelectControl
                            label={__('Is logged in', 'simpleshop-cz')}
                            value={simpleShopIsLoggedIn}
                            options={simpleShopYesNoSelect}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopIsLoggedIn: selectedSpacingOption
                                });
                            }}
                        />
                        <TextControl
                            label={__('Days to view', 'simpleshop-cz')}
                            value={simpleShopDaysToView}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopDaysToView: selectedSpacingOption
                                });
                            }}
                        />
                        <ToggleControl
                            label={__('Ignore date limits', 'simpleshop-cz')}
                            help={__('Check to completely disable limiting post access by date', 'simpleshop-cz')}
                            checked={simpleShopIgnoreDates}
                            onChange={() => props.setAttributes({simpleShopIgnoreDates: !simpleShopIgnoreDates})}
                        />
                        {!simpleShopIgnoreDates &&
                        <>
                            <h4>{__('From date', 'simpleshop-cz')}</h4>
                            <DateTimePicker
                                label={__('From date', 'simpleshop-cz')}
                                currentDate={simpleShopSpecificDateFrom}
                                value={simpleShopSpecificDateFrom}
                                onChange={(selectedSpacingOption) => {
                                    props.setAttributes({
                                        simpleShopSpecificDateFrom: selectedSpacingOption
                                    });
                                }}
                                is12Hour={is12HourTime}
                            />
                            <h4>{__('To date', 'simpleshop-cz')}</h4>
                            <DateTimePicker
                                label={__('To date', 'simpleshop-cz')}
                                currentDate={simpleShopSpecificDateTo}
                                value={simpleShopSpecificDateTo}
                                onChange={(selectedSpacingOption) => {
                                    props.setAttributes({
                                        simpleShopSpecificDateTo: selectedSpacingOption
                                    });
                                }}
                                is12Hour={is12HourTime}
                            />
                        </>
                        }
                    </PanelBody>
                </InspectorControls>
            </>
        );
    };
}, 'withInspectorControls');

wp.hooks.addFilter('editor.BlockEdit', 'display-heading/with-inspector-controls', withInspectorControls);


const EditSimpleShop = (props) => {
    const {
        attributes,
        setAttributes,
        className,
    } = props;

    const [loaded, setLoaded] = useState(false);
    const [products, setProducts] = useState(null);

    const loadProducts = () => {
        let formData = new FormData();
        formData.append('action', 'load_simple_shop_products');
        fetch(ajaxurl, {
            method: "post",
            body: formData
        })
            .then(function (response) {
                setLoaded(true);
                return response.json();
            })
            .then(function (json) {
                let select = [{label: __('Choose the Product', 'simpleshop-cz'), value: ''}];

                Object.keys(json).forEach(function (key) {
                    select.push(
                        {
                            label: json[key],
                            value: key
                        });
                });

                setProducts(select)
            });
    }

    const reloadProducts = () => {
        setLoaded(false);
        loadProducts();
    }

    if (!loaded) {
        loadProducts();
    }
    return (
        <div className={className}>
            <div>{__('SimpleShop Form', 'simpleshop-cz')} {attributes.ssFormId}</div>

            <InspectorControls key="inspector">
                <PanelBody>
                    {loaded ?
                        <>
                            <SelectControl
                                className={'simpleshop-form-select'}
                                label={__('SimpleShop Form', 'simpleshop-cz')}
                                description={__('Select the SimpleShop Form', 'simpleshop-cz')}
                                options={products}
                                value={attributes.ssFormId}
                                onChange={(ssFormId) => setAttributes({ssFormId})}
                            />
                            <Button onClick={() => reloadProducts()}>
                                {__('Reload forms', 'simpleshop-cz')}
                            </Button>
                        </> : <>Loading</>
                    }
                </PanelBody>
            </InspectorControls>
        </div>
    );
};

const SaveSimpleShop = () => null;


registerBlockType('simpleshop/simpleshop-form', {
    title: __('SimpleShop Form', 'simpleshop-cz'),
    icon: 'shield',
    category: 'common',
    keywords: [
        __('SimpleShop', 'simpleshop-cz'),
        __('Form', 'simpleshop-cz'),
    ],
    edit: EditSimpleShop,
    save: SaveSimpleShop,
    deprecated: [v1],
});