import { GetStaticProps } from "next";

import { CategorySchema, ProductSchema } from "lib/interfaces/schema";
import MetaHead from "components/MetaHead";
import ProductList from "components/ProductList/ProductList";
import Hero from "components/hero";
import FAQ from "components/faq";
import Banner from "components/banner";
import { Specs } from "components/specifications";
import { Reviews } from "components/reviews";
import { InfiniteSlider } from "components/image-slider";
import CardGrid from "components/list";

interface HomeProps {
  categories: CategorySchema[];
  products: ProductSchema[];
}

const Home: React.FC<HomeProps> = ({ categories, products }) => {
  return (
    <>
      <MetaHead description="An eCommerce app that is built by NextJS, Sanity and Stripe." />
      {/* <h1 className="main-heading text-center">Shop all you want!</h1> */}
      {/* {categories && <CategoryList categories={categories} />} */}

      <Hero />
      <div className=" justify-center mx-auto">
        <h2 className=" text-center text-4xl uppercase leading-8 text-gray-800 my-6 font-bold w-full mx-auto ">Discover our exceptional mobility products<br /> designed to enhance your lifestyle.</h2>
        <CardGrid />
      </div>


      <FAQ />

      <Banner />


      {/* <Specs /> */}

      <Reviews />
      {/* <div className="bg-white">
        <InfiniteSlider durationOnHover={75} gap={30}>
          <img
            src='/temp.webp'
            alt='Dean blunt - Black Metal 2'
            className='aspect-square w-[340px] rounded-[4px]'
          />
          <img
            src='/temp.webp'
            alt='Jungle Jack - JUNGLE DES ILLUSIONS VOL 2'
            className='aspect-square w-[340px] rounded-[4px]'
          />
          <img
            src='/temp.webp'
            alt='Yung Lean - Stardust'
            className='aspect-square w-[340px] rounded-[4px]'
          />
          <img
            src='/temp.webp'
            alt='Lana Del Rey - Ultraviolence'
            className='aspect-square w-[340px] rounded-[4px]'
          />
          <img
            src='/temp.webp'
            alt='A$AP Rocky - Tailor Swif'
            className='aspect-square w-[340px] rounded-[4px]'
          />
          <img
            src='/temp.webp'
            alt='Midnight Miami (feat Konvy) - Nino Paid, Konvy'
            className='aspect-square w-[340px] rounded-[4px]'
          />
        </InfiniteSlider>
      </div> */}

      {/* <h2 className="main-heading text-center">On Sale!</h2> */}
      {/* {products && <ProductList products={products} />} */}
    </>
  );
};


export default Home;
