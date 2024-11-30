import { FaShoppingCart, FaStar } from "react-icons/fa";

const Card = ({ title, description, price }: any) => (
    <article className="rounded-xl bg-white p-3 shadow-lg hover:shadow-xl transform hover:scale-105 duration-300">
        <a href="#">
            <div className="relative flex items-end overflow-hidden rounded-xl">
                <img src="/temp.webp" alt={`${title} Image`} className="w-full h-48 object-cover" />
            </div>
            <div className="mt-1 p-2">
                <h2 className="text-slate-700">{title}</h2>
                <p className="mt-1 text-sm text-slate-400">{description}</p>
                <div className="mt-3 flex items-end justify-between">
                    <p className="text-lg font-bold text-blue-500">${price}</p>
                    <div className="flex items-center space-x-1.5 rounded-lg bg-blue-500 px-4 py-1.5 text-white duration-100 hover:bg-blue-600">
                        <FaShoppingCart className="h-4 w-4" />
                        <button className="text-sm">Add to cart</button>
                    </div>
                </div>
            </div>
        </a>
    </article>
);

const CardGrid = () => (
    <section className="py-10 bg-inherit">
        <div className="mx-auto grid max-w-6xl grid-cols-1 gap-6 p-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <Card title="Adobe Photoshop CC 2022" description="Lisbon, Portugal" price="850" />
            <Card title="The Hilton Hotel" description="Lisbon, Portugal" price="850" />
            <Card title="The Hilton Hotel" description="Lisbon, Portugal" price="450" />
            <Card title="The Hilton Hotel" description="Lisbon, Portugal" price="450" />
        </div>
    </section>
);

export default CardGrid;
