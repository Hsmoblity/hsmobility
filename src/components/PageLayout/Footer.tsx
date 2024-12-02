import Link from "next/link";
import { FaFacebook, FaTwitter, FaInstagram, FaSpotify, FaYoutube } from "react-icons/fa";

const Footer = () => {
  const d = new Date();

  return (
    <div className="w-full bg-inherit">
      <div className="bg-[url('/nnnoise.svg')] bg-cover bg-repeat px-5 py-10">
        <div className="max-w-[1200px] mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Logo */}
          <div className="flex flex-col items-center lg:col-span-1">
            <img src="/logo.png" alt="Logo" className="w-44 object-contain mb-4" />
          </div>

          {/* Follow Us */}
          <div className="text-center">
            <h2 className="text-2xl font-bold mb-4">Follow Us</h2>
            <div className="flex justify-center gap-4 text-3xl text-gray-700">
              <FaInstagram className="cursor-pointer hover:text-black" />
              <FaFacebook className="cursor-pointer hover:text-black" />
              <FaTwitter className="cursor-pointer hover:text-black" />
              <FaYoutube className="cursor-pointer hover:text-black" />
              {/* <FaSpotify className="cursor-pointer hover:text-black" /> */}
            </div>
          </div>

          {/* Find Us */}
          <div className="text-center">
            <h2 className="text-2xl font-bold mb-4">Find Us</h2>
            <p className="text-lg text-gray-700">
              Grind<br />
              3495 Rebecca St<br /> #207 Oakville, ON<br />L6L 6X9
            </p>
          </div>

          {/* Contact Us */}
          <div className="text-center">
            <h2 className="text-2xl font-bold mb-4">Contact Us</h2>
            <p className="text-lg text-gray-700">
              All enquiries: <Link href="mailto:hsmobilityinc@gmail.com" className="text-sky-600">hsmobilityinc@gmail.com</Link><br />
              {/* PR: <Link href="mailto:grind@emergelimited.com" className="text-blue-600">grind@emergelimited.com</Link> */}
            </p>
          </div>

          {/* Legal Links
          <div className="text-center lg:col-span-2">
            <h2 className="text-2xl font-bold mb-4">Legal</h2>
            <ul className="text-lg text-gray-700 space-y-2">
              <li>Privacy Policy</li>
              <li>Terms & Conditions</li>
              <li>Pod Machine Warranty</li>
              <li>Rewards Terms</li>
              <li>Delivery</li>
            </ul>
          </div> */}

          {/* Footer Note */}

        </div>
      </div>

      {/* Bottom Line */}
      <p className="text-md text-gray-700 text-center my-4">
        &copy; {d.getFullYear()} Designed and developed by{" "}
        <Link
          href="https://harbourfrontwebdesigns.com/"
          target="_blank"
          rel="noreferrer"
        >
          <b className="underline">Harbourfront Web Designs</b>
        </Link>
      </p>
    </div>
  );
};

export default Footer;
