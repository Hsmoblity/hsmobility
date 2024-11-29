import * as React from "react";

export function Specs() {
    return (
        <div className="flex flex-col items-center pt-11 pr-8 pl-8 bg-slate-50 max-md:px-5">
            <div className="flex flex-col w-full max-w-[1200px] max-md:max-w-full">
                <div className="flex flex-wrap justify-center w-full min-h-[351px] max-md:max-w-full">
                    <div className="flex flex-col flex-1 shrink px-4 basis-0 max-w-[600px] min-w-[240px] max-md:max-w-full">
                        <img
                            loading="lazy"
                            src="/temp.webp" className="object-contain w-full aspect-[1.75] max-md:max-w-full"
                        />
                    </div>
                    <div className="flex flex-col px-4 min-w-[240px] ">
                        <div className="flex flex-1 size-full max-md:max-w-full">
                            <div className="flex flex-col px-11 min-w-[420px] w-[486px] max-md:px-5">
                                <div className="w-full text-3xl font-medium leading-none whitespace-nowrap">
                                    Specifications
                                </div>
                                <div className="flex flex-col pl-10 mt-9 w-full text-lg max-md:pl-5">
                                    <div className="py-1.5 w-full text-lg leading-none">
                                        166W x 259H x 336L (mm)
                                    </div>
                                    <div className="py-1.5 w-full leading-none">
                                        19 bar high-pressure pump
                                    </div>
                                    <div className="py-1.5 w-full leading-none">
                                        1.2l removable water tank
                                    </div>
                                    <div className="py-1.5 w-full leading-none">
                                        Adjustable drip trayProgrammable dosing
                                    </div>
                                    <div className="py-1.5 w-full leading-none">
                                        Power-saver mode
                                    </div>
                                    <div className="py-1.5 w-full leading-none">
                                        UK: 220V–240V, 50-60Hz, 1250-1450W
                                    </div>
                                    <div className="py-1.5 w-full text-lg leading-none">
                                        US: 110V-120V - 50Hz
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}