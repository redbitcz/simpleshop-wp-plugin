import assign from "lodash.assign";

const {__} = wp.i18n;
const {Component} = wp.element;
const {registerBlockType} = wp.blocks;
const {InspectorControls,} = wp.editor;
const {__experimentalGetSettings} = wp.date;
const {addFilter} = wp.hooks;
const {
	PanelBody,
	SelectControl,
	TextControl,
	DateTimePicker,
	Button
} = wp.components;

const {createHigherOrderComponent} = wp.compose;
const {Fragment} = wp.element;


class Inspector extends Component {
	constructor(props) {
		super(...arguments);
	}

	render() {

		const {setAttributes, attributes: {ssFormId}} = this.props;
		let products = [];
		$.each(ssGutenbergVariables.products, function (k, v) {
			products.push(
				{
					label: v,
					value: k
				}
			)
		});
		return (
			<InspectorControls key="inspector">
				<PanelBody>
					<SelectControl
						className={'simpleshop-form-select'}
						label={__('Form')}
						description={__('Select the SimpleShop Form')}
						options={products}
						value={ssFormId}
						onChange={(value) => this.props.setAttributes({ssFormId: value})}
					/>
					<Button
						className={'simpleshop-reload'}
					>
						{__('Reload forms')}
					</Button>
				</PanelBody>
			</InspectorControls>
		);
	}
}

class SimpleShopFormEdit extends Component {
	render() {
		const {
			attributes: {
				ssFormId
			},
			setAttributes
		} = this.props;

		return [
			<Inspector
				{...{setAttributes, ...this.props}}
			/>,
			<div id="cgb-testimonial" className="cgb-testimonial">
				SimpleShop Form {ssFormId}
			</div>
		];
	}
}

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
	edit: SimpleShopFormEdit,
	save: function (props) {
		const {attributes: {ssFormId}} = props;
		let url = 'https://form.simpleshop.cz/iframe/js/?id=' + ssFormId;
		return (
			<div class="simpleshop-form">
				<script type="text/javascript" src={url}></script>
			</div>
		);
	},
});


/**
 * Add spacing control attribute to block.
 *
 * @param {object} settings Current block settings.
 * @param {string} name Name of block.
 *
 * @returns {object} Modified block settings.
 */
const addSimpleShoplAttributes = (settings, name) => {// Use Lodash's assign to gracefully handle if attributes are undefined
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
addFilter('blocks.registerBlockType', 'simpleshop/attributes/custom', addSimpleShoplAttributes);

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
			<Fragment>
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
						<DateTimePicker
							label={__('From date', 'ss')}
							currentDate={simpleShopSpecificDateFrom}
							value={simpleShopSpecificDateFrom}
							onChange={(selectedSpacingOption) => {
								props.setAttributes({
									simpleShopSpecificDateFrom: selectedSpacingOption
								});
							}}
							is12Hour={is12HourTime}
						/>
						<DateTimePicker
							label={__('To date', 'ss')}
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
			</Fragment>
		);
	};
}, 'withInspectorControls');

wp.hooks.addFilter('editor.BlockEdit', 'display-heading/with-inspector-controls', withInspectorControls);

jQuery('body').on('click', '.simpleshop-reload', function () {
	jQuery.post(ajaxurl, {action: 'load_simple_shop_products'}, function (response) {
		let simleshopFormSelect = $('.simpleshop-form-select select');
		simleshopFormSelect.find('option').remove();
		jQuery.each(response, function (index, value) {

			simleshopFormSelect.append('<option value="' + index + '">' + value + '</option>');
		});
	}, 'json');
});
