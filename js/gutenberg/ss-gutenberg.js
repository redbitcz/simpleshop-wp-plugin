import assign from "lodash.assign";
import React, {useState} from 'react';
import {registerBlockType} from '@wordpress/blocks';
import {__} from '@wordpress/i18n';
import {InspectorControls} from '@wordpress/block-editor';
import {PanelBody, SelectControl, TextControl, DateTimePicker, Button} from '@wordpress/components';
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
        label: 'Choose',
        value: ''
    },
    {
        label: 'Yes',
        value: 'yes'
    },
    {
        label: 'No',
        value: 'no'
    }
];

const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const {
            simpleShopGroup,
            simpleShopIsMember,
            simpleShopIsLoggedIn,
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
        let simpleShopGroups = [{label: 'None', value: ''}];
        for (let item in simpleShopGroupsData) {
            simpleShopGroups.push({label: simpleShopGroupsData[item], value: item});
        }

        return (
            <>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody
                        title={__('Simpleshop Settings')}
                        initialOpen={true}
                    >
                        <SelectControl
                            label={__('Group', 'ss')}
                            value={simpleShopGroup}
                            options={simpleShopGroups}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopGroup: selectedSpacingOption
                                });
                            }}
                        />
                        <SelectControl
                            label={__('Is member', 'ss')}
                            value={simpleShopIsMember}
                            options={simpleShopYesNoSelect}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopIsMember: selectedSpacingOption
                                });
                            }}
                        />
                        <SelectControl
                            label={__('Is logged in', 'ss')}
                            value={simpleShopIsLoggedIn}
                            options={simpleShopYesNoSelect}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopIsLoggedIn: selectedSpacingOption
                                });
                            }}
                        />
                        <TextControl
                            label={__('Days to view', 'ss')}
                            value={simpleShopDaysToView}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopDaysToView: selectedSpacingOption
                                });
                            }}
                        />
                        <h4>{__('From date', 'ss')}</h4>
                        <DateTimePicker
                            currentDate={simpleShopSpecificDateFrom}
                            value={simpleShopSpecificDateFrom}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopSpecificDateFrom: selectedSpacingOption
                                });
                            }}
                            is12Hour={is12HourTime}
                        />
                        <h4>{__('To date', 'ss')}</h4>
                        <DateTimePicker
                            currentDate={simpleShopSpecificDateTo}
                            value={simpleShopSpecificDateTo}
                            onChange={(selectedSpacingOption) => {
                                props.setAttributes({
                                    simpleShopSpecificDateTo: selectedSpacingOption
                                });
                            }}
                            is12Hour={is12HourTime}
                        />
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
                let select = [{label: __('Select product', 'simpleshop-cz'), value: ''}];

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
            <div>SimpleShop Form {attributes.ssFormId}</div>

            <InspectorControls key="inspector">
                <PanelBody>
                    {loaded ?
                        <>
                            <SelectControl
                                className={'simpleshop-form-select'}
                                label={__('Form')}
                                description={__('Select the SimpleShop Form')}
                                options={products}
                                value={attributes.ssFormId}
                                onChange={(ssFormId) => setAttributes({ssFormId})}
                            />
                            <Button onClick={() => reloadProducts()}>
                                {__('Reload forms')}
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
    title: __('SimpleShop Form'),
    icon: 'shield',
    category: 'common',
    keywords: [
        __('SimpleShop'),
        __('form'),
    ],
    attributes: {
        ssFormId: {
            type: 'string',
            default: 'Choose form'
        }
    },
    edit: EditSimpleShop,
    save: SaveSimpleShop,
});
