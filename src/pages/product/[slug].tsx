import { AnimatedSubscribeButton } from "components/btn";
import { Carousel, CarouselContent, CarouselItem } from "components/pictureCarousal";
import { useState } from "react";

import { FaBuyNLarge, FaCartPlus } from "react-icons/fa";
import { MdCheckCircleOutline } from "react-icons/md";


const ITEMS = new Array(4).fill(null).map((_, index) => index + 1);

const IMAGE_URLS = [
    '/temp.webp',
    '/temp.webp',
    '/temp.webp',
    '/temp.webp',
];

function Hero() {
    const [index, setIndex] = useState(0);

    return (
        <div className="flex flex-col items-center py-8 mt-16 bg-slate-200">
            <div className="absolute top-0 left-0 w-full h-[400px] flex-1 bg-sky-300  bg-[url('/nnnoise.svg')] bg-cover bg-repeat z-0 "></div>
            <div className="flex max-w-screen-2xl  flex-row w-full justify-center items-start gap-10 z-10">

                {/* Image Section */}
                <div className='relative w-full max-w-md py-8'>
                    <Carousel index={index} onIndexChange={setIndex}>
                        <CarouselContent className='relative'>
                            {ITEMS.map((item, idx) => {
                                return (
                                    <CarouselItem key={item} className='p-4'>
                                        <div className='flex aspect-square items-center justify-center border border-zinc-200 dark:border-zinc-800'>
                                            <img src={IMAGE_URLS[idx]} alt={`Image ${item}`} className="w-full h-full object-cover" />
                                        </div>
                                    </CarouselItem>
                                );
                            })}
                        </CarouselContent>
                    </Carousel>
                    <div className='flex w-full justify-center space-x-3 px-4'>
                        {ITEMS.map((item, idx) => {
                            return (
                                <button
                                    key={item}
                                    type='button'
                                    aria-label={`Go to slide ${item}`}
                                    onClick={() => setIndex(idx)}
                                    className='h-20 w-20 border border-zinc-200 dark:border-zinc-800'
                                >
                                    <img src={IMAGE_URLS[idx]} alt={`Thumbnail ${item}`} className="w-full h-full object-cover" />
                                </button>
                            );
                        })}
                    </div>
                </div>
                <div className="flex flex-col">
                    {/* Details Section */}
                    <div className="flex flex-col gap-6 w-full max-w-[600px] border-2 p-4 bg-white drop-shadow-lg m-4 rounded-lg">
                        {/* Product Title */}

                        <p className="md:text-sm text-md text-gray-600 mx-8">
                            <span className="font-bold">Shop • </span>
                            <span className="font-mono hover:text-blue-300">  The Acorn 130 stairlift for straight staircases </span>

                        </p>
                        <h1 className="md:text-4xl text-xl font-bold text-gray-800 mx-8">
                            The Acorn 130 stairlift for straight staircases
                        </h1>
                        {/* Product Description */}
                        <p className="md:text-lg text-md text-gray-600 mx-8">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
                        </p>
                        <div className="border-b-[0.2px]"></div>

                        {/* Price and Add to Cart */}
                        <div className="flex justify-between items-center mt-6">
                            <span className="group inline-flex items-center">
                                Get a Free Quote{" "}
                            </span>

                            <AnimatedSubscribeButton
                                buttonColor="#000000"
                                buttonTextColor="#ffffff"
                                subscribeStatus={false}
                                initialText={
                                    <span className="group inline-flex items-center">
                                        Get a Free Quote{" "}
                                    </span>
                                }
                                changeText={
                                    <span className="group inline-flex items-center">
                                        <MdCheckCircleOutline className="mr-2 size-4" />
                                        Item added to Cart{" "}
                                    </span>
                                }
                            />
                        </div>
                    </div>
                    <div className="flex flex-col gap-6 w-full max-w-[600px] border-2 p-4 bg-white drop-shadow-lg m-4 rounded-lg">

                        {/* Similar Products */}
                        <div className="mt-10">
                            <h2 className="text-2xl font-semibold text-gray-800">Similar Products</h2>
                            <div className="flex gap-8 mt-6">
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/temp.webp"
                                        alt="30 Pod Mix"
                                        className="object-cover w-full aspect-[1.61]"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">For curved staircases</p>
                                    <p className="text-lg text-slate-600 m-2">The Acorn 180 stairlift for curved staircases</p>
                                    <AnimatedSubscribeButton
                                        buttonColor="#000000"
                                        buttonTextColor="#ffffff"
                                        subscribeStatus={false}
                                        initialText={
                                            <span className="group inline-flex items-center">
                                                Shop Now{" "}
                                                <FaCartPlus className="ml-1 size-4 transition-transform duration-300 group-hover:translate-x-3" />
                                            </span>
                                        }
                                        changeText={
                                            <span className="group inline-flex items-center">
                                                <MdCheckCircleOutline className="mr-2 size-4" />
                                                Item added to Cart{" "}
                                            </span>
                                        }
                                    />
                                </div>
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/temp.webp"
                                        alt="100 Pod Box"
                                        className="object-cover w-full aspect-[1.61]"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">For outdoor spaces</p>
                                    <p className="text-lg text-slate-600 m-2">The Acorn 160 stairlift for  outdoor spaces</p>
                                    <AnimatedSubscribeButton
                                        buttonColor="#000000"
                                        buttonTextColor="#ffffff"
                                        subscribeStatus={false}
                                        initialText={
                                            <span className="group inline-flex items-center">
                                                Shop now{" "}
                                                <FaCartPlus className="ml-1 size-4 transition-transform duration-300 group-hover:translate-x-3" />
                                            </span>
                                        }
                                        changeText={
                                            <span className="group inline-flex items-center">
                                                <MdCheckCircleOutline className="mr-2 size-4" />
                                                Item added to Cart{" "}
                                            </span>
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Hero;
