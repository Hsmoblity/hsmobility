import Image from "next/image";



function Hero() {


    return (
        <>
            <section className="relative z-10 w-full bg-sky-300 bg-[url('/nnnoise.svg')] bg-cover bg-repeat px-6 py-24 xl:px-10 flex flex-col">
                <div className="flex md:flex-row flex-col">
                    <h1 className="text-left ml-20 w-full pt-10 text-black font-mono font-semibold text-6xl leading-tight -space-y-10">
                        Express Your<br />
                        <span className="inline-block border-t-8 border-black w-60 pb-5"></span> Freedom with HS Mobility,
                    </h1>
                    <div className="md:block relative hidden mt-6 h-64 w-full max-w-[660px] justify-end mx-auto overflow-hidden  md:h-80 lg:h-[420px]">
                        <div className="h-24 w-24 border-4 bg-gray-500 absolute top-8 left-12 rounded-full">
                            <Image
                                src="/face1.jpg"
                                className="object-cover h-20 w-20 rounded-full"
                                fill
                                alt="A scenic view of mountains"

                            />
                        </div>
                        <div className="h-24 w-24 border-4 bg-gray-500 absolute top-8 left-32 rounded-full z-10">
                            <Image
                                src="/face2.jpg"
                                className="object-cover h-20 w-20 rounded-full"
                                fill
                                alt="A scenic view of mountains"

                            />
                        </div>
                        <div className="h-24 w-24 border-4 bg-gray-500 absolute top-8 left-52 rounded-full z-20">
                            <Image
                                src="/face3.jpg"
                                className="object-cover h-20 w-20 rounded-full"
                                fill
                                alt="A scenic view of mountains"

                            />
                        </div>
                        <div className="text-2xl absolute top-16 mt-20 px-14"> Empowering 1000+ Satisfied Customers to Stay Independent with Exceptional mobility products</div>
                        <button className="absolute top-64 left-12 inline-flex h-12 overflow-hidden rounded-full p-[2px] focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-50">
                            <span className="absolute  inset-[-1000%] animate-[spin_2s_linear_infinite] bg-[conic-gradient(from_90deg_at_50%_50%,#a2aeff_0%,#3749be_50%,#a2aeff_100%)] " />
                            <span className="inline-flex h-full w-full cursor-pointer items-center justify-center rounded-full bg-black px-8 py-1 text-md uppercase font-medium  text-white backdrop-blur-3xl">
                                Learn more
                            </span>
                        </button>
                    </div>
                </div>


                <div className="flex md:flex-row flex-col">
                    <div className="relative mt-6 h-64 w-full max-w-[860px] overflow-hidden  bg-transparent   md:h-80 lg:h-[420px]">

                        <Image
                            src="/acorn-stairlifts-home-banner-new.png"
                            className="object-cover object-right transform scale-x-[-1]"
                            fill
                            alt="A scenic view of mountains"
                            style={{
                                clipPath: "polygon( 0% 83.333%,0% 83.333%,0% 77.583%,0% 72%,0% 66.583%,0% 61.333%,0% 56.25%,0% 51.333%,0% 46.583%,0% 42%,0% 37.583%,0% 33.333%,0% 33.333%,1.375% 28.5%,3% 24%,4.875% 19.833%,7% 16%,9.375% 12.5%,12% 9.333%,14.875% 6.5%,18% 4%,21.375% 1.833%,25% 0%,87.5% 0%,87.5% 0%,89.875% 0.167%,92% 0.667%,93.875% 1.5%,95.5% 2.667%,96.875% 4.167%,98% 6%,98.875% 8.167%,99.5% 10.667%,99.875% 13.5%,100% 16.667%,100% 16.667%,100% 22.417%,100% 28%,100% 33.417%,100% 38.667%,100% 43.75%,100% 48.667%,100% 53.417%,100% 58%,100% 62.417%,100% 66.667%,100% 66.667%,98.625% 71.5%,97% 76%,95.125% 80.167%,93% 84%,90.625% 87.5%,88% 90.667%,85.125% 93.5%,82% 96%,78.625% 98.167%,75% 100%,75% 100%,68.75% 100%,62.5% 100%,56.25% 100%,50% 100%,43.75% 100%,37.5% 100%,31.25% 100%,25% 100%,18.75% 100%,12.5% 100%,12.5% 100%,10.125% 99.833%,8% 99.333%,6.125% 98.5%,4.5% 97.333%,3.125% 95.833%,2% 94%,1.125% 91.833%,0.5% 89.333%,0.125% 86.5%,0% 83.333% )"
                            }}
                        />

                    </div>
                    <div className="relative  w-full max-w-[660px] justify-end mx-auto  md:h-80 lg:h-[420px]">
                        <div className="mt-10">
                            <h2 className="text-3xl font-semibold text-gray-800">Explore our Bestsellers</h2>
                            <div className="flex gap-8 mt-6">
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/130-stairlift-hinge.jpg"
                                        alt="30 Pod Mix"
                                        className="object-cover w-64 aspect-[1.61] rounded-lg"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">Acorn Straight Stairlifts</p>
                                    <p className="text-lg text-slate-600 m-3 line-clamp-3">The ultimate staircase solution, giving you the full use of the home you love.</p>
                                    <button className="relative inline-flex h-12 overflow-hidden rounded-full p-[2px] focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-50">
                                        <span className="absolute  inset-[-1000%] animate-[spin_2s_linear_infinite] bg-[conic-gradient(from_90deg_at_50%_50%,#a2aeff_0%,#3749be_50%,#a2aeff_100%)] " />
                                        <span className="inline-flex h-full w-full cursor-pointer items-center justify-center rounded-full bg-black px-8 py-1 text-md uppercase font-medium  text-white backdrop-blur-3xl">
                                            Get a Free Quote
                                        </span>
                                    </button>
                                </div>
                                <div className="flex flex-col items-center border-[0.2px] pb-2">
                                    <img
                                        loading="lazy"
                                        src="/180-stairlift-moving.png"
                                        alt="100 Pod Box"
                                        className="object-cover w-80 aspect-[1.61] rounded-lg"
                                    />
                                    <p className="text-xl uppercase text-gray-800 font-bold m-3">Acorn Curved Stairlifts</p>
                                    <p className="text-lg text-slate-600 m-3">A comfortable and reliable ride designed for any curved staircases</p>
                                    <button className="relative inline-flex h-12 overflow-hidden rounded-full p-[2px] focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 focus:ring-offset-gray-50">
                                        <span className="absolute  inset-[-1000%] animate-[spin_2s_linear_infinite] bg-[conic-gradient(from_90deg_at_50%_50%,#a2aeff_0%,#3749be_50%,#a2aeff_100%)] " />
                                        <span className="inline-flex h-full w-full cursor-pointer items-center justify-center rounded-full bg-black px-8 py-1 text-md uppercase font-medium  text-white backdrop-blur-3xl">
                                            Get a Free Quote
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>



            </section>
            <div className="h-56 relative bottom-20 bg-sky-300 bg-[url('/nnnoise.svg')] bg-cover bg-repeat w-full -skew-y-6 "></div>
        </>

    );
}

export default Hero;
