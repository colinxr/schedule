import { cn } from "@/lib/utils"
import { Separator } from "@/components/ui/separator"

interface ConversationCardProps {
  clientName: string
  lastMessage: string
  timestamp: string
  isSelected?: boolean
  onClick?: () => void
  showSeparator?: boolean
}

export default function ConversationCard({
  clientName,
  lastMessage,
  timestamp,
  isSelected = false,
  onClick,
  showSeparator = true,
}: ConversationCardProps) {
  return (
    <>
      <div
        onClick={onClick}
        className={cn(
          "p-4 hover:bg-accent cursor-pointer transition-colors",
          isSelected && "bg-accent"
        )}
      >
        <div className="flex justify-between items-start">
          <span className="font-medium">{clientName}</span>
          <span className="text-sm text-muted-foreground">{timestamp}</span>
        </div>
        <p className="text-sm text-muted-foreground truncate mt-1">
          {lastMessage}
        </p>
      </div>
      {showSeparator && <Separator />}
    </>
  )
} 