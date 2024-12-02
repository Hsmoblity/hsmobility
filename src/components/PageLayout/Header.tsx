import Cart from "./Cart/Cart";
import { useCallback, useContext, useEffect, useState } from "react";
import CartItemsContext from "contexts/cartItemsContext";
import CartVisibilityContext from "contexts/cartVisibilityContext";
import { CartProduct } from "lib/interfaces";
import { MdShoppingCart } from "react-icons/md";
import { DrawOutlineButton } from "components/btn";
import Drawer from "components/drawer";

const Header = () => {
  const { cart } = useContext(CartItemsContext);
  const { toggleCartVisibility } = useContext(CartVisibilityContext);
  const cartLength = cart.reduce(
    (count: number, item: CartProduct) =>
      (count += item.quantity ? item.quantity : 1),
    0
  );
  const menuItems = [
    { name: "Shop All", href: "#shop" },
    { name: "Acorn Stairlifts", href: "/product/acorn-stairlifts-acorn-180-curved-stairlift" },
    { name: "Contact Us", href: "#contact-us" },
    { name: "Reviews", href: "#reviews" },
    { name: "FAQs", href: "#faq" },
  ];
  const [lastScrollY, setLastScrollY] = useState(0);
  const [scrollDirection, setScrollDirection] = useState<string | null>(null);
  const [isScrolled, setIsScrolled] = useState(false);



  const handleScroll = useCallback(() => {
    const currentScrollY = window.scrollY;
    if (currentScrollY > lastScrollY) {
      setScrollDirection("down");
    } else {
      setScrollDirection("up");
    }
    setLastScrollY(currentScrollY);

    if (currentScrollY > 50) { // Adjust this value based on when you want the background to appear
      setIsScrolled(true);
    } else {
      setIsScrolled(false);
    }
  }, [lastScrollY]);

  useEffect(() => {
    const onScroll = () => {
      requestAnimationFrame(handleScroll);
    };
    window.addEventListener("scroll", onScroll);
    return () => {
      window.removeEventListener("scroll", onScroll);
    };
  }, [handleScroll]);

  return (
    <>
      <Cart />
      <div className={`py-2 transition-transform duration-500  ${isScrolled ? `bg-[#f1ebe0] ` : `bg-[url('/nnnoise.svg')] bg-cover bg-repeat`}`}>
        <a href="/" className="flex md:hidden items-center ml-6 mt-6">
          <img
            src="/logo.png"
            alt="Logo"
            className="h-10 object-contain"
          />
        </a>
        <div className="md:hidden absolute top-5 right-5 flex flex-row ">
          <button onClick={toggleCartVisibility} className="relative z-50 outline-0 text-white items-start mt-2 mr-3 rounded-md border-1  flex flex-row" >

            <MdShoppingCart
              color="black"
              className="ml-2"
              size={50}
            />
            {cartLength > 0 && (
              <span className="absolute w-4 h-4 text-black text-xs border border-solid border-gray-500 rounded-full flex flex-row justify-center items-center p-2 -left-1 -bottom-1 bg-white">
                {cartLength}
              </span>
            )}
          </button>
          <Drawer />
        </div>
      </div>
      <header className={`md:flex hidden flex-row justify-between z-50 md:px-4 md:pb-2 p-1 font-medium  w-full capitalize transition-transform duration-500  ${isScrolled ? `bg-[#f1ebe0] ` : ` bg-[url('/nnnoise.svg')] bg-cover bg-repeat`}`}>

        <div className="w-full mx-auto flex justify-between  max-w-7xl px-6">
          {/* Logo */}

          <a href="/" className="flex items-center">
            <img
              src="/logo.png"
              alt="Logo"
              className="h-10 object-contain"
            />
          </a>
          {/* Navigation Menu */}
          <nav className="flex space-x-6 text-lg">
            {menuItems.map((item, index) => (
              <a key={index} href={item.href} className="uppercase font-bold font-poppins tracking-widest">
                <DrawOutlineButton>{item.name}</DrawOutlineButton>
              </a>
            ))}
          </nav>


          {/* Icons */}
          <div className="flex items-center space-x-4 text-lg">
            {/* <FaSearch className="cursor-pointer hover:text-gray-700" title="Search" />
            <FaHeart className="cursor-pointer hover:text-gray-700" title="Wishlist" />
            <FaUser className="cursor-pointer hover:text-gray-700" title="Account" /> */}


            <button onClick={toggleCartVisibility} className="relative z-50 outline-0 text-white px-4 py-1 bg-black rounded-md border-1 flex flex-row" >
              Your Cart
              <MdShoppingCart
                color="white"
                className="ml-2"
                size={30}
              />
              {cartLength > 0 && (
                <span className="absolute w-4 h-4 text-black text-xs border border-solid border-gray-500 rounded-full flex flex-row justify-center items-center p-2 -left-1 -bottom-1 bg-white">
                  {cartLength}
                </span>
              )}
            </button>
          </div>
        </div>
      </header>
    </>
  );
};

export default Header;
