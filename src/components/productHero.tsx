import { AnimatedSubscribeButton } from "components/btn";
import { Carousel, CarouselContent, CarouselItem } from "components/pictureCarousal";
import { useState } from "react";

import { FaBuyNLarge, FaCartPlus } from "react-icons/fa";
import { MdCheckCircleOutline } from "react-icons/md";
interface ProductHeroProps {
    product: {
        title: string;
        shortDescription: string;
        featuredImage: string;
        productPictures?: { fields: { file: { url: string } } }[];
        price: number;
        slug: string;
    };
}

const ProductHero: React.FC<ProductHeroProps> = ({ product }) => {
    const [index, setIndex] = useState(0);

    // Generate image URLs with fallback to temp.webp
    const IMAGE_URLS =
        product.productPictures?.length
            ? product.productPictures.map((pic) => pic.fields.file.url)
            : product.featuredImage
                ? [product.featuredImage]
                : ['/temp.webp'];

    return (
        <div className="flex flex-col items-center py-8 mt-16 bg-[url('/nnnoise.svg')] bg-cover bg-repeat">
            <div className="flex max-w-screen-2xl flex-row w-full justify-center items-start gap-10 z-10">
                {/* Image Section */}
                <div className="relative w-full max-w-md py-8">
                    <Carousel index={index} onIndexChange={setIndex}>
                        <CarouselContent className="relative">
                            {IMAGE_URLS.map((url, idx) => (
                                <CarouselItem key={idx} className="py-4">
                                    <div className="flex aspect-square items-center justify-center  ">
                                        <img
                                            src={url}
                                            alt={`Product Image ${idx + 1}`}
                                            className=" object-cover object-center"
                                        />
                                    </div>
                                </CarouselItem>
                            ))}
                        </CarouselContent>
                    </Carousel>
                    <div className="flex w-full justify-center space-x-3 px-4">
                        {IMAGE_URLS.map((url, idx) => (
                            <button
                                key={idx}
                                type="button"
                                aria-label={`Go to slide ${idx + 1}`}
                                onClick={() => setIndex(idx)}
                                className={`h-20 w-20 border ${idx === index
                                    ? 'border-blue-500'
                                    : 'border-zinc-200 dark:border-zinc-800'
                                    }`}
                            >
                                <img
                                    src={url}
                                    alt={`Thumbnail ${idx + 1}`}
                                    className="w-full h-full object-cover"
                                />
                            </button>
                        ))}
                    </div>
                </div>
                <div className="flex flex-col">
                    {/* Details Section */}
                    <div className="flex flex-col gap-6 w-full max-w-[600px] border-2 p-4 bg-white drop-shadow-lg m-4 rounded-lg">
                        {/* Product Title */}

                        <p className="md:text-sm text-md text-gray-600 mx-8">
                            <span className="font-bold">Shop • </span>
                            <span className="font-mono hover:text-blue-300 hover:underline"> {product.title}</span>

                        </p>
                        <h1 className="md:text-4xl text-xl font-bold text-gray-800 mx-8 font-poppins">
                            {product.title}
                        </h1>
                        {/* Product Description */}
                        <p className="md:text-lg text-md text-gray-600 mx-8">
                            {product.shortDescription}
                        </p>
                        <div className="border-b-[0.2px]"></div>

                        {/* Price and Add to Cart */}
                        <div className="flex justify-between items-center mx-6">
                            <span className="group inline-flex items-center">
                                <a className="text-sm underline" href="#contact-us" >learn more</a>
                            </span>

                            <button className="relative inline-flex text-nowrap h-12 mt-4 overflow-hidden rounded-full p-[2px] focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-50">
                                <span className="inline-flex text-nowrap  h-full w-full cursor-pointer items-center justify-center rounded-full bg-black border-gray-400 border-b-4 border-r-4 px-8 py-1 text-md uppercase font-medium  text-white backdrop-blur-3xl">
                                    <a className="text-sm" href="#contact-us" >Get a FREE quote</a>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div className="flex flex-col gap-6 w-full max-w-[600px] border-2 p-4 bg-white drop-shadow-lg m-4 rounded-lg">
                        <div className="mt-10">
                            <h2 className="text-2xl font-semibold text-gray-800 font-poppins">You Might also Like</h2>
                            <div className="flex gap-8 mt-6">
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/180-stairlift-moving.png"
                                        alt="30 Pod Mix"
                                        className="object-cover max-w-56 aspect-[1.61]"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">For curved staircases</p>
                                    <p className="text-lg text-slate-600 m-2">The Acorn 180 stairlift for curved staircases</p>
                                    <button className="relative inline-flex text-nowrap h-12 mt-4 overflow-hidden rounded-lg">
                                        <span className="group inline-flex items-center bg-black text-white px-4 py-2">
                                            <a href="/product/acorn-stairlifts-acorn-130-straight-stairlift">Shop Now{" "}</a>
                                            <FaCartPlus className="ml-1 size-4 transition-transform duration-300 group-hover:translate-x-3" />
                                        </span>
                                    </button>


                                </div>
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/acorn-outdoor-stair-lift-uk.jpg"
                                        alt="100 Pod Box"
                                        className="object-cover max-w-56 aspect-[1.61]"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">For outdoor spaces</p>
                                    <p className="text-lg text-slate-600 m-2">The Acorn 160 stairlift for  outdoor spaces</p>
                                    <button className="relative inline-flex text-nowrap h-12 mt-4 overflow-hidden rounded-lg">
                                        <span className="group inline-flex items-center bg-black text-white px-4 py-2">
                                            <a href="/product/acorn-stairlifts-acorn-130-straight-stairlift">Shop Now{" "}</a>
                                            <FaCartPlus className="ml-1 size-4 transition-transform duration-300 group-hover:translate-x-3" />
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ProductHero;
