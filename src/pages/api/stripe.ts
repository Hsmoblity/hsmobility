import type { NextApiRequest, NextApiResponse } from "next";
import { CartProduct } from "lib/interfaces";
import Stripe from "stripe";

const stripe = new Stripe(process.env.STRIPE_SECRET_KEY ?? "", {
  apiVersion: "2024-04-10"
});

export default async function handler(
  req: NextApiRequest,
  res: NextApiResponse
) {
  if (req.method === "POST") {
    try {
      const items = req.body.items;

      const line_items = items.map((item: any) => {
        const imgUrl = item.productPictures[0]?.url || ""; // Extract the first image URL

        return {
          price_data: {
            currency: "CAD",
            product_data: {
              name: item.title,
              images: imgUrl ? [imgUrl] : [], // Stripe requires an array for images
            },
            unit_amount: item.price * 100, // Convert price to cents
          },
          adjustable_quantity: {
            enabled: true,
            minimum: 1,
          },
          quantity: item.quantity || 1,
        };
      });

      // Fetch predefined shipping rates from Stripe
      const shippingRates = await stripe.shippingRates.list({
        limit: 5, // Adjust the limit to your needs
      });

      // Map the shipping rate IDs from the fetched data
      const shippingOptions = shippingRates.data.map(rate => ({
        shipping_rate: rate.id,
      }));

      // Create the checkout session
      const session = await stripe.checkout.sessions.create({
        submit_type: "pay",
        payment_method_types: ["card"],
        billing_address_collection: "auto",
        shipping_address_collection: {
          allowed_countries: ["US", "CA"], // Adjust to your business needs
        },
        shipping_options: shippingOptions, // Use the fetched shipping options
        line_items,
        mode: "payment",
        success_url: `${req.headers.origin}/success`,
        cancel_url: `${req.headers.origin}`,
      });
      res.status(200).json(session);
    } catch (error) {
      console.error("Stripe session creation failed:", error);
      res.status(500).json({
        message: "Failed to create checkout session",
        error: error instanceof Error ? error.message : "Unknown error",
      });
    }
  } else {
    res.setHeader("Allow", "POST");
    res.status(405).end("Method Not Allowed");
  }
}

