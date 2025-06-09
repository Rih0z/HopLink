/**
 * Amazon Link Block Editor
 */

import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
    BlockControls,
    RichText
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    ToggleControl,
    Button,
    Placeholder,
    ToolbarGroup,
    ToolbarButton,
    Spinner,
    Notice
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { link as linkIcon } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

export default function Edit({ attributes, setAttributes }) {
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

    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('');
    const [isValidUrl, setIsValidUrl] = useState(false);

    const blockProps = useBlockProps({
        className: 'hoplink-amazon-block'
    });

    // URLが変更されたときの処理
    useEffect(() => {
        if (url) {
            validateAndExtractASIN(url);
        }
    }, [url]);

    // URLの検証とASIN抽出
    const validateAndExtractASIN = async (inputUrl) => {
        setIsLoading(true);
        setError('');

        try {
            const response = await apiFetch({
                path: '/hoplink/v1/validate-url',
                method: 'POST',
                data: { url: inputUrl }
            });

            if (response.success) {
                setAttributes({
                    asin: response.data.asin,
                    url: inputUrl
                });
                setIsValidUrl(true);

                // 商品情報を取得（オプション）
                if (showPrice || showImage) {
                    fetchProductInfo(response.data.asin);
                }
            } else {
                setError(response.data.message || 'URLの検証に失敗しました');
                setIsValidUrl(false);
            }
        } catch (err) {
            setError('URLの検証中にエラーが発生しました');
            setIsValidUrl(false);
        } finally {
            setIsLoading(false);
        }
    };

    // 商品情報を取得
    const fetchProductInfo = async (asinCode) => {
        try {
            const response = await apiFetch({
                path: `/hoplink/v1/product-info/${asinCode}`
            });

            if (response.success) {
                setAttributes({
                    productInfo: response.data
                });
            }
        } catch (err) {
            console.error('商品情報の取得に失敗しました:', err);
        }
    };

    // プレースホルダー表示
    if (!url) {
        return (
            <div {...blockProps}>
                <Placeholder
                    icon={linkIcon}
                    label={__('Amazon アフィリエイトリンク', 'hoplink')}
                    instructions={__('Amazon商品のURLを入力してください', 'hoplink')}
                >
                    <TextControl
                        label={__('Amazon URL', 'hoplink')}
                        value={url}
                        onChange={(value) => setAttributes({ url: value })}
                        placeholder="https://www.amazon.co.jp/dp/..."
                    />
                    <Button
                        variant="primary"
                        onClick={() => validateAndExtractASIN(url)}
                        disabled={!url || isLoading}
                    >
                        {isLoading ? <Spinner /> : __('URLを検証', 'hoplink')}
                    </Button>
                </Placeholder>
            </div>
        );
    }

    // エディタ表示
    return (
        <>
            <InspectorControls>
                <PanelBody title={__('リンク設定', 'hoplink')}>
                    <TextControl
                        label={__('Amazon URL', 'hoplink')}
                        value={url}
                        onChange={(value) => setAttributes({ url: value })}
                        help={__('Amazon商品ページのURLを入力してください', 'hoplink')}
                    />
                    {asin && (
                        <p className="hoplink-asin-info">
                            ASIN: <code>{asin}</code>
                        </p>
                    )}
                    <ToggleControl
                        label={__('新しいタブで開く', 'hoplink')}
                        checked={openInNewTab}
                        onChange={(value) => setAttributes({ openInNewTab: value })}
                    />
                </PanelBody>

                <PanelBody title={__('表示設定', 'hoplink')}>
                    <ToggleControl
                        label={__('ボタンスタイル', 'hoplink')}
                        checked={buttonStyle}
                        onChange={(value) => setAttributes({ buttonStyle: value })}
                    />
                    <ToggleControl
                        label={__('価格を表示', 'hoplink')}
                        checked={showPrice}
                        onChange={(value) => setAttributes({ showPrice: value })}
                        help={__('手動登録された商品情報から価格を表示します', 'hoplink')}
                    />
                    <ToggleControl
                        label={__('画像を表示', 'hoplink')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                        help={__('手動登録された商品情報から画像を表示します', 'hoplink')}
                    />
                </PanelBody>
            </InspectorControls>

            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon={linkIcon}
                        label={__('URLを編集', 'hoplink')}
                        onClick={() => setAttributes({ url: '' })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <div {...blockProps}>
                {error && (
                    <Notice status="error" isDismissible={false}>
                        {error}
                    </Notice>
                )}

                {isLoading && (
                    <div className="hoplink-loading">
                        <Spinner />
                        <span>{__('URLを検証中...', 'hoplink')}</span>
                    </div>
                )}

                {isValidUrl && !isLoading && (
                    <div className={`hoplink-preview ${buttonStyle ? 'button-style' : 'link-style'}`}>
                        {showImage && productInfo.image_url && (
                            <img
                                src={productInfo.image_url}
                                alt={productInfo.name || linkText}
                                className="hoplink-product-image"
                            />
                        )}

                        <div className="hoplink-content">
                            {productInfo.name && (
                                <h4 className="hoplink-product-name">{productInfo.name}</h4>
                            )}

                            <RichText
                                tagName={buttonStyle ? 'span' : 'a'}
                                value={linkText}
                                onChange={(value) => setAttributes({ linkText: value })}
                                placeholder={__('リンクテキストを入力', 'hoplink')}
                                className={buttonStyle ? 'hoplink-button' : 'hoplink-link'}
                            />

                            {showPrice && productInfo.price && (
                                <p className="hoplink-price">
                                    ¥{Number(productInfo.price).toLocaleString()}
                                </p>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}