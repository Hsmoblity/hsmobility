import MetaHead from "components/MetaHead";
import FAQ from "components/faq";
import Banner from "components/banner";
import { Reviews } from "components/reviews";
import Form from "components/step-form";
import { useEffect, useState } from "react";
import { getProducts } from "lib/contentful/contentful";
import ProductList from "components/ProductList/ProductList";
import { GetServerSideProps } from 'next';
import ProductItem from "components/ProductList/ProductItem";

interface ContentfulProduct {
    fields: {
        title: string;
        slug: string;
        shortDescription: any;
        featuredImage?: {
            fields: {
                file: {
                    url: string;
                };
            };
        };
        productPictures?: any[];
        price: number;
        affiliate: boolean;
    };
}

type Props = {
    params: { slug: string };
    mappedProducts: any[];
    hero: any;
};

const ProductPage = ({ params, mappedProducts, hero }: Props) => {
    const { slug } = params; // Now this will definitely work

    return (
        <>
            <MetaHead description="An eCommerce app that is built by NextJS, Sanity and Stripe." />
            {hero && <ProductItem product={hero} />}
            <div className="justify-center mx-auto">
                <h2 className="text-center text-4xl uppercase leading-8 text-gray-800 my-6 font-bold font-poppins max-w-4xl mx-auto">
                    Explore a curated selection of top-notch mobility products crafted to elevate your lifestyle.
                </h2>
                <ProductList products={mappedProducts} />
            </div>
            <FAQ />
            <Banner />
            <div className="py-8">

                <Form />
            </div>
            <Reviews />
        </>
    );
};

export const getServerSideProps: GetServerSideProps = async ({ params }) => {
    const { slug } = params || {}; // Safeguard against undefined params

    if (!slug) {
        return { notFound: true }; // Optionally handle the case where slug is missing
    }

    try {
        const response = await getProducts(""); // Fetch products
        const items = response.items as ContentfulProduct[];

        const mappedProducts = items.map((item: ContentfulProduct) => ({
            title: item.fields.title,
            slug: item.fields.slug,
            shortDescription: item.fields.shortDescription,
            featuredImage: item.fields.featuredImage?.fields?.file?.url || "",
            productPictures: item.fields.productPictures || [],
            price: item.fields.price || 0,
            affiliate: item.fields.affiliate || false,
        }));

        const hero = mappedProducts.find((item) => item.slug === slug) || null;

        return {
            props: {
                params: { slug },
                mappedProducts,
                hero,
            },
        };
    } catch (error) {
        console.error("Error fetching products:", error);
        return {
            props: {
                params: { slug },
                mappedProducts: [],
                hero: null,
            },
        };
    }
};

export default ProductPage;
