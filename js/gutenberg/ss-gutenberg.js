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
        simpleShopGroups: {
            type: 'array'
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


const withInspectorControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const {
            simpleShopGroup,
            simpleShopGroups,
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
        let groups = [];
        for (let item in simpleShopGroupsData) {
            groups.push({label: simpleShopGroupsData[item], value: item});
        }

        let selectedGroups = simpleShopGroups;
        if (typeof simpleShopGroups === 'undefined') {
            if (simpleShopGroup) {
                selectedGroups = [simpleShopGroup];
            } else {
                selectedGroups = [];
            }
        }

        return (
            <>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody
                        title={__('SimpleShop Settings', 'simpleshop-cz')}
                        initialOpen={true}
                    >
                        <p><a href="https://podpora.redbit.cz/stitek/wp-plugin/" target="_blank">
                            {__('Help - SimpleShop plugin', 'simpleshop-cz')}
                        </a>
                        </p>

                        <SelectControl
                            label={__('The block will be visible to:', 'simpleshop-cz')}
                            value={simpleShopIsLoggedIn}
                            options={[
                                {
                                    label: __('Choose', 'simpleshop-cz'),
                                    value: ''
                                },
                                {
                                    label: __('Logged in user', 'simpleshop-cz'),
                                    value: 'yes'
                                },
                                {
                                    label: __('Logged out user', 'simpleshop-cz'),
                                    value: 'no'
                                }
                            ]}
                            onChange={selected => {
                                props.setAttributes({
                                    simpleShopIsLoggedIn: selected
                                });
                                if (!selected || 'no' === selected) {
                                    props.setAttributes({
                                        simpleShopIsMember: '',
                                        simpleShopGroups: []
                                    });
                                }

                            }}
                        />
                        {
                            simpleShopIsLoggedIn === 'yes' &&
                            <>
                                <SelectControl
                                    label={__('Membership', 'simpleshop-cz')}
                                    value={simpleShopIsMember}
                                    options={[
                                        {
                                            label: __('Choose', 'simpleshop-cz'),
                                            value: ''
                                        },
                                        {
                                            label: __('Is a member of:', 'simpleshop-cz'),
                                            value: 'yes'
                                        },
                                        {
                                            label: __('Is not a member of:', 'simpleshop-cz'),
                                            value: 'no'
                                        }
                                    ]}
                                    onChange={(selectedSpacingOption) => {
                                        props.setAttributes({
                                            simpleShopIsMember: selectedSpacingOption
                                        });
                                    }}
                                />

                                {
                                    groups.map(item => (
                                        <ToggleControl
                                            label={item.label}
                                            checked={selectedGroups.includes(item.value)}
                                            onChange={(checked) => {
                                                const tempGroups = [...selectedGroups];
                                                if (checked) {
                                                    tempGroups.push(item.value);
                                                } else {
                                                    const index = tempGroups.indexOf(item.value);
                                                    if (index > -1) {
                                                        tempGroups.splice(index, 1);
                                                    }
                                                }
                                                props.setAttributes({
                                                    simpleShopGroups: tempGroups
                                                });
                                            }}
                                        />
                                    ))
                                }
                            </>
                        }

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
                            help={__('Check to completely disable limiting access to the content by date', 'simpleshop-cz')}
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
                let select = [{label: __('Choose a product', 'simpleshop-cz'), value: ''}];

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
