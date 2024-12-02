import Link from "next/link";
import Image from "next/image";
import { ProductSchema } from "lib/interfaces";
// import urlFor from "lib/sanity/urlFor";
import classNames from "classnames";

interface ProductItemProps {
  product: ProductSchema;
}

const ProductItem: React.FC<ProductItemProps> = ({ product }) => {
  return (
    <div>
      <Link href={`/product/${product.slug}`}>
        <a className="relative w-full h-full">
          <div className="w-full h-64 md:mb-4 mb-2 overflow-hidden relative">
            <Image
              src={product.productPictures[0].fields.file.url}
              quality={100}
              layout="fill"
              className="clickable-img"
              alt={product.title}
            />
          </div>
        </a>
      </Link>
      <Link href={`/product/${product.slug}`}>
        <a className="relative w-full h-full">
          <div className="w-full px-1 flex flex-col items-center">
            <h3 className="text-lg uppercase font-medium text-center mb-3">
              {product.title}
            </h3>
            <div className="flex items-center flex-col">
              <span
                className={classNames("text-base mb-1",

                  "mr-3 text-gray-900"
                )}
              >
                ${product.price}
              </span>

            </div>
          </div>
        </a>
      </Link>
    </div>
  );
};

export default ProductItem;
