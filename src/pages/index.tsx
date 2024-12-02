import MetaHead from "components/MetaHead";
import Hero from "components/hero";
import FAQ from "components/faq";
import Banner from "components/banner";
import { Reviews } from "components/reviews";
import Form from "components/step-form";
import { useEffect, useState } from "react";
import { getProducts } from "lib/contentful/contentful";
import { ProductSchema } from "lib/interfaces";
import ProductList from "components/ProductList/ProductList";

interface ContentfulProduct {
  fields: {
    title: string;
    slug: string;
    shortDescription: any; // Replace `any` with the appropriate type if known
    featuredImage?: {
      fields: {
        file: {
          url: string;
        };
      };
    };
    productPictures?: any[]; // Replace `any` if you know the type
    price: number;
    affiliate: boolean;
  };
}

const Home = () => {
  const [products, setProducts] = useState<ProductSchema[]>([]);
  useEffect(() => {
    const fetchCartProducts = async () => {
      try {
        const response = await getProducts("");
        const items = response.items as ContentfulProduct[]; // Cast response.items to the correct type
        setProducts(
          items.map((item: ContentfulProduct) => ({
            title: item.fields.title,
            slug: item.fields.slug,
            shortDescription: item.fields.shortDescription,
            featuredImage: item.fields.featuredImage?.fields?.file?.url || "",
            productPictures: item.fields.productPictures || [],
            price: item.fields.price || 0,
            affiliate: item.fields.affiliate || false,
          }))
        );
      } catch (error) {
        console.error("Error fetching products:", error);
      }
    };

    fetchCartProducts();
  }, []);
  return (
    <>
      <MetaHead description="An eCommerce app that is built by NextJS, Sanity and Stripe." />
      <Hero />
      <div className=" justify-center mx-auto">
        <h2 className=" text-center text-4xl uppercase leading-8 text-gray-800 my-6 font-bold font-poppins max-w-4xl mx-auto ">Explore a curated selection of top-notch mobility products crafted to elevate your lifestyle.</h2>
        <ProductList products={products} />
      </div>
      <div>
        <FAQ />
      </div>
      <Banner />
      <div className="py-8">

        <Form />
      </div>

      {/* <Specs /> */}
      <div >
        <Reviews />
      </div>
    </>
  );
};


export default Home;
