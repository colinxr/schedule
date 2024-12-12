import type { Metadata } from "next";
import QueryProvider from "./providers/QueryProvider";
import "./globals.css";

export const metadata: Metadata = {
  title: "Schedule",
  description: "Artist scheduling application",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="h-full">
      <body className="antialiased">
        <QueryProvider>
          {children}
        </QueryProvider>
      </body>
    </html>
  );
}
