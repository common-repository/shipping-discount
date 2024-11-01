(function ($) {
	'use strict';

	$('body').ready(() => {

		const discount_type = $('#discount_type');
		const shipping_discount_type = $('#shipping_discount_type');

		$('.discount_type_field').after($('.shipping_discount_type_field'));
		triggerHidden(null);

		discount_type.on('change', () => {
			triggerHidden(null);
			shipping_discount_type.trigger('change');
		});

		if (discount_type === 'shipping_discount') {
			triggerHidden(shipping_discount_type.val());
		}

		shipping_discount_type.on('change', (evt) => {
			const value = $(evt.target).val().trim();
			triggerHidden(value);
			$('.free_shipping_field').attr('hidden', (discount_type.val().trim() === 'shipping_discount'));
			$('.shipping_discount_max_amount_field').attr('hidden', (value !== 'percentage') );
		});

		function triggerHidden(value) {
			$('.coupon_amount_field').attr('hidden', ((discount_type.val().trim() === 'shipping_discount') && (value === 'free')));
			$('.free_shipping_field').attr('hidden', (discount_type.val().trim() === 'shipping_discount'));
			$('.shipping_discount_type_field').attr('hidden', !(discount_type.val().trim() === 'shipping_discount'));
			$('.shipping_discount_max_amount_field').attr('hidden', (discount_type.val().trim() !== 'shipping_discount'));
		}

	});

})(jQuery);