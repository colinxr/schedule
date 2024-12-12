export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <main className="min-h-screen min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-green-900 flex items-center justify-center">
        <div className="flex items-center justify-center p-6 w-[400px]">
            <div className="w-full">
            {children}
            </div>
        </div>
    </main>
  );
} 