import MetaHead from "components/MetaHead";
import Hero from "components/hero";
import FAQ from "components/faq";
import Banner from "components/banner";
import { Reviews } from "components/reviews";
import CardGrid from "components/list";
import Form from "components/step-form";

interface HomeProps {
  products: any[];
}

const Home: React.FC<HomeProps> = ({ products }) => {
  return (
    <>
      <MetaHead description="An eCommerce app that is built by NextJS, Sanity and Stripe." />
      <Hero />
      <div className=" justify-center mx-auto">
        <h2 className=" text-center text-4xl uppercase leading-8 text-gray-800 my-6 font-bold w-full mx-auto ">Explore a curated selection of top-notch mobility<br /> products crafted to elevate your lifestyle.</h2>
        <CardGrid />
      </div>
      <FAQ />
      <Banner />
      <div className="py-8">
        <div className="text-4xl">
          <h2 className="text-6xl text-center font-bold text-gray-800 tracking-wide"> Get a FREE Quote</h2>

        </div>
        <p className="mx-auto text-center px-20 mt-4 justify-center max-w-2xl">Simply enter your details below and a trusted representative will be in touch to arrange a home survey and provide your FREE no obligation quotation.</p>

        <Form />
      </div>

      {/* <Specs /> */}
      <Reviews />
    </>
  );
};


export default Home;
