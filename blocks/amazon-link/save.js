/**
 * Amazon Link Block Save
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
    const {
        url,
        asin,
        linkText,
        buttonStyle,
        showPrice,
        showImage,
        productInfo,
        openInNewTab
    } = attributes;

    const blockProps = useBlockProps.save({
        className: 'hoplink-amazon-block'
    });

    // ショートコードを使用してサーバーサイドでレンダリング
    const shortcodeAttributes = [
        `url="${url}"`,
        `text="${linkText}"`,
        buttonStyle && 'button="true"',
        showPrice && 'show_price="true"',
        showImage && 'show_image="true"',
        openInNewTab && 'target="_blank"'
    ].filter(Boolean).join(' ');

    return (
        <div {...blockProps}>
            {`[hoplink_url ${shortcodeAttributes}]`}
        </div>
    );
}