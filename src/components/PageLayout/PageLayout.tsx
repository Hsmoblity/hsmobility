import Header from "./Header";
import Footer from "./Footer";
import { motion } from "framer-motion";
import Script from "next/script";

interface PageLayoutProps {
  children: React.ReactNode;
}
const fadeInVariant = {
  hidden: { opacity: 0 },
  visible: { opacity: 1, transition: { duration: 0.4 } }
};
const PageLayout: React.FC<PageLayoutProps> = ({ children }) => {
  return (
    <div>
      <motion.div
        initial="hidden"
        animate="visible"
        variants={fadeInVariant}
        id="header" className="fixed top-0 left-0 right-0 z-50 "
      >
        <Header />

      </motion.div>
      <div className=" z-10 relative min-h-[75vh] bg-[#f6f2f0] ">
        {children}
        {/* <Script
          id="tawk-to-script"
          strategy="afterInteractive"
          dangerouslySetInnerHTML={{
            __html: `
              var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
              (function(){
                var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
                s1.async = true;
                s1.src = 'https://embed.tawk.to/672418434304e3196adb786b/1ibifthpt';
                s1.charset = 'UTF-8';
                s1.setAttribute('crossorigin', '*');
                s0.parentNode.insertBefore(s1, s0);
              })();
            `,
          }}
        /> */}
      </div>
      <Footer />
    </div>
  );
};

export default PageLayout;
