// import React, { useContext, useEffect, useRef, useState } from "react";
// import Image from "next/image";
// import Link from "next/link";
// import { GetStaticProps, GetStaticPaths } from "next";
// import { ProductSchema } from "lib/interfaces";
// import CartItemsContext from "contexts/cartItemsContext";
// import Types from "reducers/cart/types";
// // import { PortableText, toPlainText } from "@portabletext/react";
// // import productsSlugsQuery from "lib/sanity/queries/products_slugs";
// // import productQuery from "lib/sanity/queries/product";
// // import urlFor from "lib/sanity/urlFor";
// // import client from "lib/sanity/client";
// import classNames from "classnames";
// import MetaHead from "components/MetaHead";
// import CartVisibilityContext from "contexts/cartVisibilityContext";
// import { getProductBySlug } from "lib/contentful/getProducts";

// interface ProductProps {
//   product: ProductSchema;
// }

// const Product: React.FC<{ params: { slug: string } }> = ({ params }) => {
//   const { toggleCartVisibility } = useContext(CartVisibilityContext);
//   const { dispatch } = useContext(CartItemsContext);
//   const [product, setProduct] = useState<ProductSchema | null>(null)
//   const [productImages, setProductImages] = useState<any>(null)
//   // const [similarProducts, setSimilarProducts] = useState<ProductSchema[]>([])
//   const [zoomedIndex, setZoomedIndex] = useState<number | null>(null)
//   const [zoomPosition, setZoomPosition] = useState({ x: 0, y: 0 })
//   const flexRef = useRef<HTMLDivElement>(null)
//   const infoContainerRef = useRef<HTMLDivElement>(null)


//   useEffect(() => {
//     const fetchProduct = async () => {

//       const product = await getProductBySlug(params.slug) as ProductSchema;

//       setProduct(product);


//     };

//     fetchProduct();
//   }, [params]);


//   useEffect(() => {
//     const updateInfoContainerHeight = () => {
//       if (flexRef.current && infoContainerRef.current) {
//         const flexHeight = flexRef.current.offsetHeight;
//         infoContainerRef.current.style.height = `${flexHeight}px`;
//       }
//     };

//     updateInfoContainerHeight();
//     window.addEventListener('resize', updateInfoContainerHeight);

//     return () => {
//       window.removeEventListener('resize', updateInfoContainerHeight);
//     };
//   }, [productImages]);

//   const handleMouseMove = (e: React.MouseEvent<HTMLDivElement>, index: number) => {
//     if (zoomedIndex !== index) return;
//     const { left, top, width, height } = e.currentTarget.getBoundingClientRect();
//     const x = ((e.clientX - left) / width) * 100;
//     const y = ((e.clientY - top) / height) * 100;
//     setZoomPosition({ x, y });
//   };

//   const handleMouseEnter = (index: number) => setZoomedIndex(index);
//   const handleMouseLeave = () => setZoomedIndex(null);


//   const {
//     _id,
//     name,
//     images,
//     description,
//     price,
//   } = product;


//   const addToCart = () => {
//     dispatch({
//       type: Types.addToCart,
//       payload: { ...product }
//     });

//     toggleCartVisibility();
//   };


//   return (
//     <>
//       {/* {product?.name && (
//         <MetaHead
//           title={product.name}
//           description={product.description}
//         />
//       )} */}

//       <div className="flex sm:flex-row flex-col justify-between w-full max-w-2xl mx-auto sm:mt-8 mt-3">
//         <div className="overflow-hidden relative sm:w-2/5 w-full sm:mb-0 mb-5 h-80">
//           {product?.featured_image && (
//             <Image
//               onMouseMove={e => handleMouseMove(e, 0)}
//               onMouseEnter={() => handleMouseEnter(0)}
//               onMouseLeave={handleMouseLeave}
//               src={images[0]}
//               layout="fill"
//               quality={100}
//               className="object-cover"
//               alt={product.name}
//             />
//           )}
//         </div>
//         <div className="sm:w-3/5 w-full sm:pl-6 sm:pr-0 pl-5 pr-5 ">
//           <h1 className="text-4xl text-left font-bold text-gray-900 sm:truncate mb-8">
//             {product?.name}
//           </h1>
//           <h2 className="mb-6">
//             <span className="text-xl text-gray-900 mr-2">Price: </span>
//             <span
//               className={classNames("text-2xl mb-1", {
//                 "line-through text-gray-400 mr-3": product?.on_sale,
//                 "text-gray-900": !product?.on_sale
//               })}
//             >
//               ${product?.price}
//             </span>
//             {product?.on_sale && (
//               <span className="text-red-600 text-2xl ">
//                 NOW ${product?.sale_price}
//               </span>
//             )}
//           </h2>
//           <h3 className="text-xl text-gray-900 mr-2">Description:</h3>
//           {product?.description && (
//             <div className="text-gray-600 text-sm mb-5">
//               {/* <PortableText value={product?.description} /> */}
//             </div>
//           )}

//           <button
//             onClick={addToCart}
//             className="bg-black px-6 py-3 text-white text-xs uppercase hover:bg-[#ffeddf] hover:text-black border-black border-2 transition-colors duration-500"
//           >
//             Add To Cart
//           </button>
//         </div>
//       </div>
//     </>
//   );
// };




// // export const getStaticProps: GetStaticProps = async ({ params }) => {
// //   const product = await client.fetch(productQuery, {
// //     slug: params?.slug
// //   });

// //   if (!product) {
// //     throw Error("Sorry, something went wrong.");
// //   }

// //   return {
// //     props: { product },
// //     revalidate: 100
// //   };
// // };

// // export const getStaticPaths: GetStaticPaths = async () => {
// //   const slugs = await client.fetch(productsSlugsQuery);

// //   const paths = slugs.map((item: { slug: string }) => ({
// //     params: { slug: item.slug }
// //   }));

// //   return {
// //     paths,
// //     fallback: true
// //   };
// // };

// export default Product;


import { AnimatedSubscribeButton } from "components/btn";
import { Carousel, CarouselContent, CarouselItem } from "components/productCarousal";
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
