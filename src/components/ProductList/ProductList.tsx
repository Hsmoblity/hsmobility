import { ProductSchema } from "lib/interfaces";
import { FaShoppingCart } from "react-icons/fa";



const Card = ({ title, shortDescription, affiliate, slug, price, featuredImage, productPictures }: ProductSchema) => {
  // Determine the image to use
  const imageSrc = (productPictures[0].fields.file.url || "/temp.webp");

  return (
    <article className="rounded-xl bg-white p-3 shadow-lg hover:shadow-xl transform hover:scale-105 duration-300">
      <a href={`/product/${slug}`}>
        <div className="relative flex items-end overflow-hidden rounded-xl">
          <img
            src={imageSrc}
            alt={`${title} Image`}
            className="w-full h-48 object-cover"
          />
        </div>
        <div className="mt-1 p-2">
          <h2 className="text-slate-700 font-poppins font-black">{title}</h2>
          <p className="mt-1 text-sm text-slate-400 font-semibold line-clamp-3">
            {shortDescription || "No description available"}
          </p>
          <div className="mt-3 flex items-end justify-between">
            {affiliate ? (
              // Affiliate-specific UI
              <>
                <button className="text-md font-bold text-black underline">
                  Learn More
                </button>
                <div className="flex items-center space-x-1.5 rounded-lg bg-black px-4 py-1.5 text-white duration-100 hover:bg-slate-800">
                  <button className="text-sm">Get a Quote</button>
                </div>
              </>
            ) : (
              // Non-affiliate product UI
              <>
                <p className="text-lg font-bold text-blue-500">${price.toFixed(2)}</p>
                <div className="flex items-center space-x-1.5 rounded-lg bg-black px-4 py-1.5 text-white duration-100 hover:bg-slate-800">
                  <button className="text-sm">Add to cart</button>
                  <FaShoppingCart className="h-4 w-4" />
                </div>
              </>
            )}
          </div>
        </div>
      </a>
    </article>
  );
};


const ProductList = ({ products }: { products: ProductSchema[] }) => (
  <section id="shop" className="py-10 bg-inherit">
    <div className="mx-auto grid max-w-6xl grid-cols-1 gap-6 p-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      {products.map((product, index) => (
        <Card key={index} {...product} />
      ))}
    </div>
  </section>
);

export default ProductList;