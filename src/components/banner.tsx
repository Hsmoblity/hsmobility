import * as React from "react";
import { AnimatedSubscribeButton } from "./btn";
import { FaArrowRight, FaCartPlus } from "react-icons/fa";
import { MdCheckCircleOutline } from "react-icons/md";

function Banner() {
    return (
        <div className="bg-sky-300 bg-[url('/nnnoise.svg')] bg-auto bg-repeat w-5/6 rounded border-b-2 border-gray-300 shadow-xl justify-center mx-auto flex-1 py-2 px-2">
            <div className="max-w-screen-xl mx-auto flex flex-col md:flex-row items-center justify-between">
                {/* Image Section */}
                <div className="flex flex-col w-full md:w-1/4 mb-8 md:mb-0 mx-4">
                    <img
                        loading="lazy"
                        src="/temp.webp"
                        className="object-contain rounded-md aspect-[1.25] w-full"
                    />
                </div>

                {/* Text Section */}
                <div className="flex flex-col w-full md:w-1/2 text-center md:text-left mb-8 md:mb-0">
                    <div className=" mx-3 text-5xl font-medium leading-none ">
                        New Accessories are here.
                    </div>
                    <div className="flex flex-col pt-3 mx-3 w-full">
                        <div className="flex gap-1">
                            <div className="flex-auto text-base font-bold">Check out our latest mobility Products and Accessories. </div>
                        </div>
                    </div>
                </div>

                {/* Call to Action Section */}
                <div className="flex flex-col w-full md:w-1/4 text-base text-[var(--grind-co-uk-white)] text-center md:text-left">
                    <AnimatedSubscribeButton
                        buttonColor="#000000"
                        buttonTextColor="#ffffff"
                        subscribeStatus={false}
                        initialText={
                            <span className="group inline-flex items-center">
                                Check out{" "}
                                <FaArrowRight className="ml-1 size-4 transition-transform duration-300 group-hover:translate-x-3" />
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
    );
}

export default Banner;
