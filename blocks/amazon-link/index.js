/**
 * Amazon Link Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import save from './save';
import './style.css';
import './editor.css';

registerBlockType('hoplink/amazon-link', {
    edit: Edit,
    save,
    
    example: {
        attributes: {
            url: 'https://www.amazon.co.jp/dp/B08XYZ1234',
            linkText: 'この商品をAmazonで見る',
            buttonStyle: true
        }
    }
});