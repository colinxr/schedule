import type { Metadata } from "next";
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
      <body className="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-green-900 text-gray-100 antialiased">
        <main className="min-h-screen flex items-center justify-center p-4">
          {children}
        </main>
      </body>
    </html>
  );
}
